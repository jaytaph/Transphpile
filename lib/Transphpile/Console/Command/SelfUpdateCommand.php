<?php

namespace Transphpile\Console\Command;

// Code based on self-update from php-cs-fixer:
// https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/1.12/Symfony/CS/Console/Command/SelfUpdateCommand.php
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(array('selfupdate'))
            ->setDescription('Update transphpile.phar to the latest version.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command replace your transphpile.phar by the latest version.
<info>php transphpile.phar %command.name%</info>
EOT
            )
        ;
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->isInstalledAsPhar()) {
            $output->writeln('<error>Self-update is available only for PHAR version.</error>');
            return 1;
        }
        if (false !== $remoteVersion = @file_get_contents('https://transphpile.jaytaph.nl/transphpile.version')) {
            if ($this->getApplication()->getVersion() === $remoteVersion) {
                $output->writeln('<info>transphpile is already up to date.</info>');
                return;
            }
        }
        $remoteFilename = 'https://transphpile.jaytaph.nl/transphpile.phar';
        $localFilename = $_SERVER['argv'][0];
        $tempFilename = basename($localFilename, '.phar').'-tmp.phar';
        if (false === @file_get_contents($remoteFilename)) {
            $output->writeln('<error>Unable to download new versions from the server.</error>');
            return 1;
        }
        try {
            copy($remoteFilename, $tempFilename);
            chmod($tempFilename, 0777 & ~umask());
            // test the phar validity
            $phar = new \Phar($tempFilename);
            // free the variable to unlock the file
            unset($phar);
            rename($tempFilename, $localFilename);
            $output->writeln('<info>transphpiler updated.</info>');
        } catch (\Exception $e) {
            if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                throw $e;
            }
            unlink($tempFilename);
            $output->writeln(sprintf('<error>The download is corrupt (%s).</error>', $e->getMessage()));
            $output->writeln('<error>Please re-run the self-update command to try again.</error>');
            return 1;
        }
    }

    public function isInstalledAsPhar()
    {
        static $result;
        if (null === $result) {
            $result = 'phar://' === substr(__DIR__, 0, 7);
        }
        return $result;
    }

}
