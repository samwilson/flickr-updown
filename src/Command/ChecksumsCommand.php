<?php

declare(strict_types=1);

namespace Samwilson\PhpFlickrCli\Command;

use Exception;
use Samwilson\PhpFlickr\PhotosApi;
use Samwilson\PhpFlickr\PhpFlickr;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ChecksumsCommand extends CommandBase
{
    /** @var string */
    protected $tmpDir;

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription($this->msg('command-checksums-desc'));
        $this->addOption('hash', null, InputOption::VALUE_OPTIONAL, $this->msg('option-hash-desc'), 'md5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        // Set up the temporary directory.
        $this->tmpDir = sys_get_temp_dir() . '/phpflickr-cli';
        $filesystem = new Filesystem();

        if (!$filesystem->exists($this->tmpDir)) {
            $filesystem->mkdir($this->tmpDir, 0755);
            $this->io->success($this->msg('created-tmp-dir', [$this->tmpDir]));
        }

        $flickr = $this->getFlickr($input);

        // Get all photos.
        $page = 1;

        do {
            $photos = $flickr->people()->getPhotos(
                'me',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'o_url, tags',
                500,
                $page
            );

            if (0 === $photos['pages']) {
                $this->io->warning($this->msg('no-photos-found'));

                return 0;
            }

            $this->io->writeln($this->msg('page-i-of-n', [$page, $photos['pages']]));

            foreach ($photos['photo'] as $photo) {
                // Process this photo.
                $hashTag = $this->processPhoto($input, $flickr, $photo);

                if (!$hashTag) {
                    return 1;
                }
            }

            $page++;
        } while ($photos['page'] !== $photos['pages']);

        // Clean up the temporary directory.
        $tmpFiles = scandir($this->tmpDir, SORT_ASC);

        foreach (preg_grep('|^\..*|', $tmpFiles, PREG_GREP_INVERT) as $file) {
            unlink($file);
        }

        rmdir($this->tmpDir);
        $this->io->success($this->msg('deleted-tmp-dir', [$this->tmpDir]));

        return 0;
    }

    /**
     * @param string[] $photo
     * @return string|bool The hash machine tag, or false.
     * @throws Exception
     */
    protected function processPhoto(InputInterface $input, PhpFlickr $flickr, array $photo)
    {
        $hashInfo = $this->getHashInfo($input);
        $shortUrl = $flickr->urls()->getShortUrl($photo['id']);

        // See if the photo has already got a checksum tag.
        preg_match("/(checksum:{$hashInfo['name']}=.*)/", $photo['tags'], $matches);

        if (isset($matches[1])) {
            // If it's already got a tag, do nothing more.
            $this->io->writeln($this->msg('already-has-checksum', [$photo['id'], $shortUrl]));

            return $matches[1];
        }

        // Download the file.
        $photoInfo = $flickr->photos()->getInfo($photo['id']);
        $originalUrl = $flickr->urls()->getImageUrl($photoInfo, PhotosApi::SIZE_ORIGINAL);
        $tmpFilename = $this->tmpDir . '/checksumming.' . $photoInfo['originalformat'];
        $downloaded = copy($originalUrl, $tmpFilename);

        if (false === $downloaded) {
            $this->io->error($this->msg('unable-to-download', [$photo['id'], $shortUrl]));

            return false;
        }

        // Calculate the file's hash, and remove the temporary file.
        $fileHash = $hashInfo['function']($tmpFilename);

        if (file_exists($tmpFilename)) {
            unlink($tmpFilename);
        }

        // Upload the new tag if it's not already present.
        $hashTag = "checksum:{$hashInfo['name']}=$fileHash";
        $tagAdded = $flickr->photos()->addTags($photo['id'], [$hashTag]);

        if (isset($tagAdded['err'])) {
            throw new Exception($tagAdded['err']['msg']);
        }

        $this->io->writeln($this->msg('added-checksum', [$photo['id'], $shortUrl]));

        return $hashTag;
    }
}
