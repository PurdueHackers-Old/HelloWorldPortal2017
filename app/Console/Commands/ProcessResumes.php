<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Checkin;
use DB;
use \Symfony\Component\Process\Process;

use File;
use Storage;

class ProcessResumes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resumes:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process resumes in /storage/app/copied_resumes to /storage/app/cleaned_resumes';

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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $this->info('Processing resumes...');
      $sourcePath = "./storage/app/copied_resumes/";
      $destPath = "./storage/app/cleaned_resumes/";
      $storagePathInput = "copied_resumes/";
      $storagePathOutput = "cleaned_resumes/";

      //Show warning if input files do not already exist
      if(!File::exists($sourcePath)) {
        // This folder doesnt exist
        $this->info("The ".$sourcePath." folder does not exist! Aborting...");
        $this->info('Aborting...');
        return;
      }
      //Show warning if output files  already exist
      if(File::exists($destPath)) {
        // This folder already exists
        $this->info("The ".$destPath." folder already exists! Aborting...");
        $this->info('Aborting...');
        return;
      }

      $checkedInUsers = DB::table('users')
        ->join('checkins','users.id','checkins.user_id')
        ->join('resumes','users.id','resumes.user_id')
        ->select('users.*','checkins.*','resumes.*')
        ->get();

      foreach ($checkedInUsers as $user) {
        $resume_name = "resume_".$user->uuid.".pdf";
        $new_name = "resume_".$user->firstname.".".$user->lastname.".pdf";
        $this->info("User ".$user->email." resume: ".$resume_name);
        Storage::disk('local')->move($storagePathInput.$resume_name, $storagePathOutput.$new_name);
      }
    }
}
