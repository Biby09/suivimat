<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/image/image.php';
class ImageManager{

    public const UPLOAD_DIR = '/upload';
    public const MAX_SIZE = 1600;       // plus grand côté, en pixels
    public const ALLOWED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Photos d'un article (hors photos de dégâts)
     */
    public function getArticleImages(Article $article): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM images WHERE idx_article = :article AND idx_degat IS NULL ORDER BY id_image');
        $stmt->execute(['article' => $article->getId()]);
        $images = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $image = new Image();
            $image->hydrate($ligne);
            $images[] = $image;
        }

        return $images;
    }

    /**
     * Première photo de chaque article demandé : [id_article => url]
     */
    public function getCoverUrls(array $articleIds): array
    {
        if (!$articleIds) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($articleIds), '?'));
        $stmt = $this->pdo->prepare("SELECT images.idx_article, images.im_name FROM images JOIN (SELECT MIN(id_image) AS id_image FROM images WHERE idx_article IN ($placeholders) AND idx_degat IS NULL GROUP BY idx_article) AS covers ON covers.id_image = images.id_image");
        $stmt->execute(array_map('intval', array_values($articleIds)));
        $urls = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $urls[(int) $ligne['idx_article']] = '/upload/' . $ligne['im_name'] . '.jpg';
        }

        return $urls;
    }

    /**
     * Vrai si le fichier est une image JPEG, PNG ou WebP lisible
     */
    public static function isValidImage(string $path): bool
    {
        $info = @getimagesize($path);
        return $info !== false && in_array($info[2], self::ALLOWED_TYPES, true);
    }

    /**
     * Photos envoyées dans le champ $_FILES[$field] (envoi multiple « photos[] »), vérifiées :
     * renvoie les chemins des fichiers temporaires, ou lève une InvalidArgumentException dont le message
     * peut être affiché tel quel (photo trop lourde, pas une image, trop de photos…).
     */
    public static function getUploadedPhotos(string $field, int $max): array
    {
        $files = $_FILES[$field] ?? null;
        if (!$files || !is_array($files['error'])) {
            return [];
        }

        $paths = [];
        foreach ($files['error'] as $index => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new InvalidArgumentException('Une photo est trop volumineuse');
            }
            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($files['tmp_name'][$index])) {
                throw new InvalidArgumentException('L\'envoi d\'une photo a échoué');
            }
            if (!self::isValidImage($files['tmp_name'][$index])) {
                throw new InvalidArgumentException('Seules les photos JPEG, PNG ou WebP sont acceptées');
            }
            $paths[] = $files['tmp_name'][$index];
        }

        if (count($paths) > $max) {
            throw new InvalidArgumentException('Vous pouvez ajouter au maximum ' . $max . ' photos à la fois');
        }
        return $paths;
    }

    /**
     * Ajoute des photos à un article, ou à un dégât de cet article si $damageId est donné.
     * Tout ou rien : en cas d'erreur, aucun fichier ni aucune ligne n'est conservé.
     * Si une transaction est déjà ouverte (ex. retour d'un prêt avec dégât), elle est utilisée : l'appelant valide ou annule.
     * $paths : fichiers temporaires déjà validés (getUploadedPhotos() ou isValidImage()).
     * Chaque image est ré-encodée en JPEG (supprime les métadonnées, dont la position GPS) et réduite si besoin.
     */
    public function addArticleImages(Article $article, array $paths, ?int $damageId = null): void
    {
        $written = [];
        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare('INSERT INTO images (im_name, idx_article, idx_degat) VALUES (:name, :article, :degat)');
            foreach ($paths as $path) {
                $name = self::uuid();
                $destination = $_SERVER['DOCUMENT_ROOT'] . self::UPLOAD_DIR . '/' . $name . '.jpg';

                self::saveAsJpeg($path, $destination);
                $written[] = $destination;
                $stmt->execute(['name' => $name, 'article' => $article->getId(), 'degat' => $damageId]);
            }

            if ($ownTransaction) {
                $this->pdo->commit();
            }
        } catch (Exception $exception) {
            if ($ownTransaction) {
                $this->pdo->rollBack();
            }
            foreach ($written as $file) {
                @unlink($file);
            }
            throw $exception;
        }
    }

    /**
     * Supprime des photos d'un article (lignes + fichiers). Les ID qui ne sont pas des photos de cet article
     * (autre article, photo de dégât) sont ignorés : on ne peut pas supprimer la photo d'un autre en trichant sur l'ID.
     */
    public function deleteArticleImages(Article $article, array $imageIds): void
    {
        $imageIds = array_values(array_unique(array_map('intval', $imageIds)));
        if (!$imageIds) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($imageIds), '?'));
        $where = "idx_article = ? AND idx_degat IS NULL AND id_image IN ($placeholders)";
        // ...[a, b] (spread) : l'ID de l'article puis tous les ID d'images, dans l'ordre des « ? »
        $params = [$article->getId(), ...$imageIds];

        $stmt = $this->pdo->prepare("SELECT im_name FROM images WHERE $where");
        $stmt->execute($params);
        $names = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo->prepare("DELETE FROM images WHERE $where")->execute($params);

        // Fichiers supprimés après les lignes : au pire un fichier orphelin, jamais une ligne sans fichier
        foreach ($names as $name) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . self::UPLOAD_DIR . '/' . $name . '.jpg');
        }
    }

    private static function saveAsJpeg(string $source, string $destination): void
    {
        $image = @imagecreatefromstring(file_get_contents($source));
        if ($image === false) {
            throw new Exception('Image illisible : ' . $source);
        }

        // Photos de téléphone : on applique l'orientation EXIF avant de la perdre
        if (function_exists('exif_read_data') && getimagesize($source)[2] === IMAGETYPE_JPEG) {
            $orientation = @exif_read_data($source)['Orientation'] ?? 1;
            $rotation = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($rotation !== 0) {
                $image = imagerotate($image, $rotation, 0);
            }
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, self::MAX_SIZE / max($width, $height));
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        // Fond blanc : les zones transparentes (PNG, WebP) ne deviennent pas noires en JPEG
        $output = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
        imagecopyresampled($output, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = imagejpeg($output, $destination, 85);

        if (!$saved) {
            throw new Exception('Écriture impossible : ' . $destination);
        }
    }

    // UUID v4 (36 caractères), utilisé comme nom de fichier
    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
