<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use File;

class DownloadResumes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resumes:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Download files from S3 into ./storage/app/copied_resumes
     *
     * @return mixed
     */
    public function handle()
    {
      $this->info('Downloading resumes...');
      $bucketName = getenv('AWS_BUCKET');
      $targetPath = "./storage/app/copied_resumes";

      if(!$bucketName) {
        $this->info('Undefined bucket name! Set AWS_BUCKET env variable');
        return;
      }
      $downloadCommand = "aws s3 cp --recursive s3://".$bucketName."/resumes ".$targetPath;


      $this->info("Copying resumes from AWS bucket: ".$bucketName);
      $this->info("Copying resumes into RELATIVE path (check working dir!): ".$targetPath);
      $this->info("Command to Execute:\n".$downloadCommand);

      if(!$this->confirm("Are you sure?")) {
        $this->info('Aborting...');
        return;
      }

      //Show warning if files already exist
      if(File::exists($targetPath)) {
        // This folder already exists
        if(!$this->confirm("A folder already exists on the target path! It will be overwritten.\nDo you want to continue anyways?")) {
          $this->info('Aborting...');
          return;
        }
      }

      $process = new Process($downloadCommand);
      $process->run();
      if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
      } else {
        $this->info("Output:".$process->getOutput());
        $this->info("Success");
      }
    }
}
