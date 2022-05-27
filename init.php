<?php

$dir = getcwd() . DIRECTORY_SEPARATOR;

if(is_dir($libgamespyquery = $dir . "src" . DIRECTORY_SEPARATOR . "libgamespyquery")) removeDirectory($libgamespyquery);
if(is_dir($libasynql = $dir . "poggit" . DIRECTORY_SEPARATOR . "poggit")) removeDirectory($libasynql);
if(is_dir($libasynCurl = $dir . "src" . DIRECTORY_SEPARATOR . "libasynCurl")) removeDirectory($libasynCurl);

copyDirectory($dir . "vendor" . DIRECTORY_SEPARATOR . "mmm545" . DIRECTORY_SEPARATOR . "libgamespyquery" . DIRECTORY_SEPARATOR . "src", $dir . "src");
copyDirectory($dir . "vendor" . DIRECTORY_SEPARATOR . "sof3" . DIRECTORY_SEPARATOR . "libasynql" . DIRECTORY_SEPARATOR . "libasynql" . DIRECTORY_SEPARATOR . "src", $dir . "src");
copyDirectory($dir . "vendor" . DIRECTORY_SEPARATOR . "nethergamesmc" . DIRECTORY_SEPARATOR . "libasyncurl", $dir . "src" . DIRECTORY_SEPARATOR . "libasynCurl");

print("Initialize Done!");

function copyDirectory(string $from, string $to) : void{
	@mkdir($to, 0777, true);
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
	foreach($files as $fileInfo){
		$target = str_replace($from, $to, $fileInfo->getPathname());
		if($fileInfo->isDir()) @mkdir($target, 0777, true);
		else{
			$contents = file_get_contents($fileInfo->getPathname());
			file_put_contents($target, $contents);
		}
	}
}

function removeDirectory(string $dir) : void{
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
	foreach($files as $fileInfo){
		if($fileInfo->isDir()) rmdir($fileInfo->getPathname());
		else unlink($fileInfo->getPathname());
	}
	rmdir($dir);
}