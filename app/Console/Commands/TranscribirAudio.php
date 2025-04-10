<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TranscribirAudio extends Command
{
    protected $signature = 'transcribe {audio_path}';
    protected $description = 'Transcribe audio a texto usando Whisper';

    public function handle()
    {
        $audioPath = $this->argument('audio_path');
        $outputPath = $audioPath . '.txt';

        // Verificar si el archivo existe
        if (!file_exists($audioPath)) {
            $this->error("El archivo de audio no existe en: $audioPath");
            return 1;
        }

        /*$python = base_path('scripts/venv/Scripts/python.exe');
        $script = base_path('scripts/transcribir.py');

        $process = new Process([
            $python,
            $script,
            $audioPath
        ]);*/

        $python = base_path('scripts/venv/Scripts/python.exe');
        $script = base_path('scripts/transcribir.py');

        $process = new Process([
            $python,
            $script,
            $audioPath
        ], null, ['PYTHONHASHSEED' => '0']); // <-- Añade el tercer parámetro con las variables de entorno


        /*$process = new Process([
            'C:\Users\Usuario\AppData\Local\Programs\Python\Python39\python.exe',
            base_path('scripts/transcribir.py'),
            $audioPath
        ]);*/

        $process->setTimeout(300); // 5 minutos de timeout

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->info($buffer);
            }
        });

        if ($process->isSuccessful()) {
            if (file_exists($outputPath)) {
                $text = file_get_contents($outputPath);
                $this->info("Transcripción completada exitosamente");
                $this->line($text); // Esto capturará Artisan::output()
                return 0;
            } else {
                $this->error("El archivo de salida no se generó correctamente");
                return 1;
            }
        } else {
            $this->error("Error en la transcripción: " . $process->getErrorOutput());
            return 1;
        }
    }
}
