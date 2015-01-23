<?php

use SensioLabs\Melody\Melody;
use SensioLabs\Melody\Configuration\RunConfiguration;
use Symfony\Component\Process\Process;
use SensioLabs\Melody\Resource\ResourceParser;

/**
 * Class which integrates melody scripts into the php-console.
 *
 * @author mstaab
 * @see https://github.com/sensiolabs/melody
 */
class MelodyPlugin {
    public function isMelodyScript($source) {
        return preg_match(ResourceParser::MELODY_PATTERN, $source);
    }

    public function isScriptingSupported() {
        // the melody lib is bundled with the console, so the only additional requirement is a composer CLI
        exec('which composer', $out, $ret);
        return $ret === 0;
    }

    public function runScript($__source_code, $__bootstrap_file)
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'melody-composer';

        // make sure the melody subprocess has a composer home,
        // which is not the case when running from webcontext
        $_ENV['COMPOSER_HOME'] = $tmpDir;

        $melody = new Melody();
        $configuration = new RunConfiguration(/*true, true*/);
        $executor = function (Process $process, $verbose)
        {
            $callback = function ($type, $text)
            {
                // we only have one output channel to the browser, just echo "all the things"
                echo $text;
            };
            $process->run($callback);
        };

        //TODO missing $__bootstrap_file support
        /*
        if ($__bootstrap_file) {
            require $__bootstrap_file;
        }
        */

        $tmpFile = tempnam($tmpDir, '_script');
        register_shutdown_function(function() use ($tmpFile) {
            @unlink($tmpFile);
        });
        file_put_contents($tmpFile, $__source_code);
        $melody->run($tmpFile, array(), $configuration, $executor);
    }
}