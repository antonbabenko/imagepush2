<?php

namespace Imagepush\ImagepushBundle\Imagine;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Liip\ImagineBundle\Imagine\Data\Loader\LoaderInterface;
use Imagine\Image\ImagineInterface;
use Knp\Bundle\GaufretteBundle\FilesystemMap;

class CustomDataLoader implements LoaderInterface
{

    /**
     * @var Imagine\Image\ImagineInterface
     */
    private $imagine;

    /**
     * @var array
     */
    private $formats;

    /**
     * @var Gaufrette\Filesystem
     */
    private $fs;

    /**
     * Constructs
     *
     * @param ImagineInterface  $imagine
     * @param array             $formats
     * @param FilesystemMap     $filesystem
     * @param string            $mapName
     */
    public function __construct(ImagineInterface $imagine, $formats, FilesystemMap $filesystem, $mapName)
    {
        $this->imagine = $imagine;
        $this->formats = $formats;

        // Get instance of filesystem map
        $this->fs = $filesystem->get($mapName);
    }

    /**
     * @param string $path
     *
     * @return Imagine\Image\ImageInterface
     */
    public function find($path)
    {

        $info = pathinfo($path);

        $name = $info['dirname'] . '/' . $info['filename'];
        $targetFormat = empty($this->formats) || in_array($info['extension'], $this->formats) ? $info['extension'] : null;

        //\D::dump($name);
        //\D::dump($path);
        //\D::dump($this->fs->has($path));

        if (empty($targetFormat) || !$this->fs->has($path)) {
            // attempt to determine path and format
            $optionalPath = null;
            foreach ($this->formats as $format) {
                if ($targetFormat !== $format
                    && $this->fs->has($name . '.' . $format)
                ) {
                    $optionalPath = $name . '.' . $format;
                    break;
                }
            }

            if (!$optionalPath) {
                throw new NotFoundHttpException(sprintf('Source image not found in "%s"', $path));
            }

            $path = $optionalPath;
        }

        $content = $this->fs->get($path)->getContent();

        return $this->imagine->load($content);
    }

}