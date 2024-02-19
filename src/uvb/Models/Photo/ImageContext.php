<?php
declare(ticks = 1);

namespace uvb\Models\Photo;

use Exception;

class ImageContext
{
    const SUPPORTED_MIME_TYPES = [
        "image/gif",
        "image/jpeg",
        "image/png"
    ];

    /**
     * @ignore
     */
    private string $binary, $mime;

    /**
     * @ignore
     */
    private bool $destroyed = false;

    /**
     * @ignore
     */
    private function __construct(string $binary, $mime)
    {
        if (strlen($binary) > 50 * 1024 * 1024)
        {
            throw new Exception("File size cannot be more than 50 MB");
        }
        $this->mime = $mime;
        $this->binary = $binary;
    }

    /**
     * Создаёт контекст изображения из бинарника изображения
     *
     * @param string $binary
     * @param string $mime
     * @return ImageContext
     * @throws Exception
     */
    public static function CreateFromBinary(string $binary, string $mime) : ImageContext
    {
        if (!in_array($mime, self::SUPPORTED_MIME_TYPES))
        {
            throw new Exception("Mime type '" . $mime . "' isn't supported");
        }

        return new ImageContext($binary, $mime);
    }

    /**
     * Создаёт контекст изображения из файла в файловом хранилище
     *
     * @param string $path
     * @return ImageContext
     * @throws Exception
     */
    public static function CreateFromFile(string $path) : ImageContext
    {
        if (!file_exists($path))
        {
            throw new Exception("File '" . $path . "' does not exist");
        }

        if (!is_file($path))
        {
            throw new Exception("'" . $path . "' is not a file, perhaps a directory");
        }

        $mime = mime_content_type($path);

        if (!in_array($mime, self::SUPPORTED_MIME_TYPES))
        {
            throw new Exception("Mime type '" . $mime . "' isn't supported. Cannot create context of file '" . $path . "'");
        }

        $binary = file_get_contents($path);

        if (!$binary)
        {
            throw new Exception("Cannot open file '" . $path . "'");
        }

        return new ImageContext($binary, $mime);
    }

    /**
     * Возвращает MIME файла
     *
     * @return string
     * @throws Exception
     */
    public function GetMime() : string
    {
        if ($this->destroyed)
        {
            throw new Exception("Object is destroyed");
        }

        return $this->mime;
    }

    /**
     * Возвращает содержимое файла
     *
     * @return string
     * @throws Exception
     */
    public function GetBinary() : string
    {
        if ($this->destroyed)
        {
            throw new Exception("Object is destroyed");
        }

        return $this->binary;
    }


    /**
     * Уничтожает данные контекста и освобождает память
     *
     * @return void
     */
    public function Destroy() : void
    {
        $this->destroyed = true;
        $this->mime = "";
        $this->binary = "";
    }

    /**
     * @return bool Уничтожен ли объект
     */
    public function IsDestroyed() : bool
    {
        return $this->destroyed;
    }
}