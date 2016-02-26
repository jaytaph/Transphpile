<?php

namespace Transphpile\Tests;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Yaml\Yaml;
use Transphpile\IO\IOInterface;
use Transphpile\IO\SymfonyIO;
use Transphpile\Transpile\Transpile;



class FunctionalTestCase extends TestCase
{

    function assertTranspile($yamlPath)
    {
        $input = new ArrayInput(array());
        $stream = fopen('php://memory', 'rw', false);
        $output = new StreamOutput($stream);
        $symfonyio = new SymfonyIO($input, $output);


        // Load and parse yaml
        $config = Yaml::parse(file_get_contents($yamlPath));
        if (! isset($config['stdout'])) {
            $this->fail('Stdout section not found in $yamlPath');
        }
        if (! isset($config['code'])) {
            $this->fail('Code section not found in $yamlPath');
        }

        // Create temp file for code to transpile
        $tmpPath = tempnam(sys_get_temp_dir(), 'transphpile');
        file_put_contents($tmpPath, "<?php\n" . $config['code']);

        // Transpile code and send to stdout
        $transpiler = new Transpile($symfonyio);
        $transpiler->transpile($tmpPath, '-');

        // unlink tmp file
        unlink($tmpPath);

        // Fetch php5 code written by transpiler
        rewind($stream);
        $php5 = stream_get_contents($stream);

        // Run php5 code
        $process = new PhpProcess($php5);
        $process->run();
        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();

        if (! empty($stderr) && ! isset($config['stderr'])) {
            $this->fail('Error reported, but no stderr section found in $yamlPath');
        }

        // Check output and error output
        $config['stdout'] = trim($config['stdout']);
        $this->assertRegExp('{'.$config['stdout'].'}', $stdout, isset($config['name']) ? $config['name'] : "");

        if (isset($config['stderr'])) {
            $config['stderr'] = trim($config['stderr']);

            if (empty($stderr)) {
                $this->fail('stderr seems empty and should be "'.$config['stderr'].'"');
            }
            $this->assertRegExp('{'.$config['stderr'].'}', $stderr, isset($config['name']) ? $config['name'] : "");
        }
    }
}
