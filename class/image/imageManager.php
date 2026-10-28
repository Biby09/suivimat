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
     * Ajoute des photos à un article. Tout ou rien : en cas d'erreur, aucun fichier ni aucune ligne n'est conservé.
     * $paths : fichiers temporaires déjà validés avec isValidImage().
     * Chaque image est ré-encodée en JPEG (supprime les métadonnées, dont la position GPS) et réduite si besoin.
     */
    public function addArticleImages(Article $article, array $paths): void
    {
        $written = [];

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('INSERT INTO images (im_name, idx_article, idx_degat) VALUES (:name, :article, NULL)');
            foreach ($paths as $path) {
                $name = self::uuid();
                $destination = $_SERVER['DOCUMENT_ROOT'] . self::UPLOAD_DIR . '/' . $name . '.jpg';

                self::saveAsJpeg($path, $destination);
                $written[] = $destination;
                $stmt->execute(['name' => $name, 'article' => $article->getId()]);
            }

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            foreach ($written as $file) {
                @unlink($file);
            }
            throw $exception;
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
