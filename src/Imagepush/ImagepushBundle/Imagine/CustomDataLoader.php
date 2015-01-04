<?php

namespace Imagepush\ImagepushBundle\Imagine;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Liip\ImagineBundle\Imagine\Data\Loader\LoaderInterface;
use Imagine\Image\ImagineInterface;
use Gaufrette\Filesystem;

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
     * @param ImagineInterface $imagine
     * @param array            $formats
     * @param Filesystem       $fs
     */
    public function __construct(ImagineInterface $imagine, $formats, Filesystem $fs)
    {
        $this->imagine = $imagine;
        $this->formats = $formats;
        $this->fs = $fs;
    }

    /**
     * @param string $path
     *
     * @return Imagine\Image\ImageInterface
     */
    public function find($path)
    {

        if (false !== strpos($path, '/../') || 0 === strpos($path, '../')) {
            throw new NotFoundHttpException(sprintf("Source image was searched with '%s' out side of the defined root path", $path));
        }

        $info = pathinfo($path);

        $name = $info['dirname'] . '/' . $info['filename'];
        $targetFormat = empty($this->formats) || in_array($info['extension'], $this->formats) ? $info['extension'] : null;

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
