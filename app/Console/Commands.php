<?php

namespace App\Console;

use App\Utils\Process;
use Illuminate\Support\Facades\Log;

/**
 * Collection of exec commands.
 * The commands return values accordingly in deug mode as well.
 */
class Commands
{
    public static function pingRouter($router)
    {
        if (!filter_var($router->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new \InvalidArgumentException("Invalid IP address: " . $router->ip);
        }
        $process = new Process([config('commands.nmap'), $router->ip, "-sP", "-oG", "-"]);
        $process->run(log: false);
        $result = $process->getOutput(debugOutput: rand(1, 10) > 9 ? "error" : 'Status: Up');
        return $result;
    }

    public static function latexToPdf($path, $outputDir)
    {
        $process = new Process([config('commands.pdflatex'), '-interaction=nonstopmode', '-output-dir', $outputDir, $path]);
        $process->run();
        $result = $process->getOutput(debugOutput: 'ok');
        return $result;
    }
}
