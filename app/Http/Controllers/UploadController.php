<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Config;
use Aws\S3\S3Client;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadbucketstore(Request $request)
    {
	$path = $request->file('anyfile')->store('vrstorage'); 
	$foldername= pathinfo($path,PATHINFO_FILENAME);
	exec('unzip -d '.\Config::get('properties.vr_path').' '.\Config::get('properties.vr_path').$foldername.".zip". ' 2>&1',$output1);
	exec('mv '.\Config::get('properties.vr_path').'"'.pathinfo($request->file('anyfile')->getClientOriginalName(),PATHINFO_FILENAME).'" '.\Config::get('properties.vr_path')."10000014". ' 2>&1',$output);
	
	unlink(\Config::get('properties.vr_path').$foldername.".zip");
	
	//dd($foldername);
	$folderToMove = \Config::get('properties.vr_path')."10000014";

	$dirLists = $this->listFolderFiles($folderToMove); //dd($dirLists);

	// Upload a complete site
	$this->uploadFolderToAWS($dirLists);	
        
       // return redirect()->route('controlpanel.cars.vrupload',$car_details->id)->with('success','Car VR images uploaded successfully.');          

    }
    
	// List all files and folders in a main folder
	function listFolderFiles($dir,$currfolder='')
	{
	    $fileInfo     = scandir($dir);
	    $allFileLists = [];	    

	    foreach ($fileInfo as $folder) {
		if ($folder !== '.' && $folder !== '..') {
		    if (is_dir($dir . DIRECTORY_SEPARATOR . $folder) === true) {
		        $allFileLists[] = $this->listFolderFiles($dir . DIRECTORY_SEPARATOR . $folder,$currfolder . "/" . $folder);
		    } else {
		        $allFileLists[] = $currfolder . "/" . $folder;
		    }		    
		}
	    }

	    return $allFileLists;
	}

	// Upload a complete folder to AWS
	function uploadFolderToAWS($dirLists)
	{
	    foreach ($dirLists as $dir) {

		if ($dir !== '.' && $dir !== '..') {
		    if (is_array($dir) === true) {
		        // Array
		        $this->uploadFolderToAWS($dir);
		    } else {
		        // file
		        
		        $this->uploadFileToS3Bucket($dir);
		    }
		}
	    }
	}

	// Upload file to AWS S3
	function uploadFileToS3Bucket($file_Path)
	{
	    

	    $bucket = 'carzso-rnd-bucket';
	    $full_path = '/var/www/html/s3upload/storage/app/vrstorage/10000014/';
	    $basefolder = "vrstorage/10000014";

	    try {
		// Instantiate an Amazon S3 client.

		
		$result = Storage::disk('s3')->put($basefolder.$file_Path, fopen($full_path.$file_Path, 'r'));
		echo "<pre>";
		print_r($result);
	    } catch (Aws\S3\Exception\S3Exception $e) {
		echo "There was an error uploading the file.\n";
		echo $e->getMessage();
	    }
	}
	

}

