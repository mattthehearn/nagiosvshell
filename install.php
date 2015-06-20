#!/usr/bin/env php
<?php
// Nagios V-Shell
// Copyright (c) 2010-2011 Nagios Enterprises, LLC.
// Install script written by Mike Guthrie <mguthrie@nagios.com>
//

// @TODO: modify this script to find and parse the main nagios.cfg file for all 
// relevant information.

///////////////////////////// DO NOT EDIT THIS FILE ///////////////////////////
//
// Custom configuration options should be added to install-config.php

date_default_timezone_set('UTC');

include(dirname(__FILE__).'/config.php');

$errors = 0;
$errorstring = '';


/////////////////////////////// Configure Apache //////////////////////////////

// Backup web directory if it already exists.
$output = system('/usr/bin/test -d '.escapeshellarg(TARGETDIR), $code);
if($code == 0)
{
	echo "Backing up current web directory...\n";
	$output = system('/bin/mv '.escapeshellarg(TARGETDIR).' '.escapeshellarg(TARGETDIR).'.'.date('Ymd\.His').'.bak', $code);
	if($code > 0)
	{
		$errors++;
		$errorstring .= "ERROR: Failed to backup existing ".TARGETDIR." directory \n$output\n";
	}
}

// Copy web files to web directory
echo "Copying files...\n";
$output = system('/bin/cp -r ./www '.escapeshellarg(TARGETDIR), $code);
if($code > 0)
{
	$errors++;
	$errorstring .= "ERROR: Failed to copy files to ".TARGETDIR." directory \n$output\n";
}

// Copy readme.md into web directory
echo "Copying readme.md file...\n";
$output = system('/bin/cp -r ./readme.md '.escapeshellarg(TARGETDIR), $code);
if($code > 0)
{
	$errors++;
	$errorstring .= "ERROR: Failed to copy readme.md into ".TARGETDIR." directory \n$output\n";
}

// Copy license.txt into web directory
echo "Copying license.txt file...\n";
$output = system('/bin/cp -r ./license.txt '.escapeshellarg(TARGETDIR), $code);
if($code > 0)
{
	$errors++;
	$errorstring .= "ERROR: Failed to copy license.txt into ".TARGETDIR." directory \n$output\n";
}

// Copy package.json into web directory
echo "Copying package.json file...\n";
$output = system('/bin/cp -r ./package.json '.escapeshellarg(TARGETDIR), $code);
if($code > 0)
{
	$errors++;
	$errorstring .= "ERROR: Failed to copy package.json into ".TARGETDIR." directory \n$output\n";
}

// Create .htaccess file with dynamic base url
echo "Creating custom .htaccess file...\n";
ob_start(); // Try to create from template file, fall back on default file
include(dirname(__FILE__).'/config/htaccess.template');
$htaccess = ob_get_clean();
if( ! file_put_contents(TARGETDIR.'/.htaccess', $htaccess) ){
	$errors++;
	$errorstring .= "ERROR: Failed to create custom htaccess file from template. ";
	$errorstring .= "Installing default file instead. Manually check .htaccess values are correct\n";
	$output = system('/bin/cp config/htaccess '.escapeshellarg(TARGETDIR.'/.htaccess'), $code);
	if($code > 0) {
		$errors++;
		$errorstring .= "ERROR: Failed to copy config/htaccess file to /www/.htaccess \n$output\n";
	}
}

// Change file ownership
echo "Updating file permissions...\n";
$output = system('/bin/chown -R '.escapeshellarg(APACHEUSER).':'.escapeshellarg(APACHEGROUP).' '.escapeshellarg(TARGETDIR), $code);
if($code > 0)
{
	$errors++;
	$errorstring .= "ERROR: Failed to update file permissions to ".APACHEUSER.":".APACHEGROUP." at ".TARGETDIR." directory \n$output\n";
}

// Create apache conf file for project
echo "Copying apache configuration file...\n";
$apache_conf_file = escapeshellarg(APACHECONFDIR).'/'.escapeshellarg(APACHECONFFILE);
$output = system('/bin/touch '.$apache_conf_file.' || /usr/bin/touch '.$apache_conf_file, $code);
if($code > 0){
	$errors++;
	$errorstring .= "ERROR: Failed to create apache configuration file in the ".APACHECONFDIR." directory \n$output\n";
}else{
	// Try to create from template file, fall back on default file
	ob_start();
	include(dirname(__FILE__).'/config/vshell_apache.conf.template');
	$apache_conf = ob_get_clean();
	if( ! file_put_contents(APACHECONFDIR.'/'.APACHECONFFILE, $apache_conf) ){
		$errors++;
		$errorstring .= "ERROR: Failed to create apache config file from template.";
		$errorstring .= "Installing default file instead. Manually check ".$apache_conf_file." values are correct\n";
		$output = system('/bin/cp config/vshell_apache.conf '.$apache_conf_file, $code);
		if($code > 0) {
			$errors++;
			$errorstring .= "ERROR: Failed to create apache configuration file from default file \n$output\n";
		}
	}
}

// Enable mod_rewrite. On by default in RHEL/CentOS. Off in Debian.
$output = system('/usr/bin/which a2enmod', $code);
if($code == 0){
	echo "Enabling Apache module rewrite on Debian family distribution...\n";
	$output = system('/usr/sbin/a2enmod rewrite', $code);
	if($code > 0){
		$errors++;
		$errorstring .= "ERROR: Failed to enable apache module rewrite \n$output\n";
	}
}

// Restart apache service
if( file_exists('/etc/init.d/httpd') ){ // RHEL
	$action = 'service';
	$service = 'httpd';
}elseif( file_exists('/etc/init.d/apache2') ){ // Debian
	$action = 'invoke-rc.d';
	$service = 'apache2';
}else{
	$action = false;
	$service = false;
}

if($service)
{
	echo "Restarting apache...\n";
	$output = system("{$action} {$service} restart", $code);
	echo $output;
	if($code > 0)
	{
		$errors++;
		$errorstring .= "ERROR: Failed to restart apache, please restart apache manually \n$output\n";
	}
}
else
{
	$errors++;
	$errorstring .= "ERROR: Failed to restart apache, could not find service name. Please restart apache manually \n$output\n";
}


///////////////////////////// Check file locations ////////////////////////////

echo "Checking for file locations...\n";

// Status file

if(file_exists( '/usr/local/nagios/var/status.dat'))
{
	$statusfile = '/usr/local/nagios/var/status.dat'; //source installs
}
elseif(file_exists('/var/nagios/status.dat'))  //yum installs
{
	$statusfile = '/var/nagios/status.dat';
}
elseif(file_exists('/var/log/nagios/status.dat'))  // centos 6.5 yum package install
{
	$statusfile = '/var/log/nagios/status.dat';
}
elseif(file_exists('/var/cache/nagios3/status.dat'))  //ubuntu debian nagios3 installs
{
	$statusfile = '/var/cache/nagios3/status.dat';
}
else
{
	$errors++;
	$errorstring .= "NOTICE: status.dat file not found.  Please specify the location of this file in your /etc/".ETC_CONF." file\n";
	$statusfile = '';
}

define('STATUSFILE', $statusfile);

// Object cache file

if(file_exists('/usr/local/nagios/var/objects.cache'))
{
	$objectfile = '/usr/local/nagios/var/objects.cache'; //source installs
}
elseif(file_exists('/var/nagios/objects.cache'))  //yum installs
{
	$objectfile = '/var/nagios/objects.cache';
}
elseif(file_exists('/var/log/nagios/objects.cache'))  // centos 6.5 yum package install
{
	$objectfile = '/var/log/nagios/objects.cache';
}
elseif(file_exists('/var/cache/nagios3/objects.cache'))  //ubuntu debian nagios3 installs
{
	$objectfile = '/var/cache/nagios3/objects.cache';
}
else
{
	$errors++;
	$errorstring .= "NOTICE: objects.cache file not found.  Please specify the location of this file in your /etc/".ETC_CONF." file\n";
	$objectfile = '';
}

define('OBJECTSFILE', $objectfile);

// cgi.cfg file

if(file_exists('/usr/local/nagios/etc/cgi.cfg'))
{
	$cgifile = '/usr/local/nagios/etc/cgi.cfg';  //source installs
}
elseif(file_exists('/etc/nagios/cgi.cfg'))  //yum installs
{
	$cgifile = '/etc/nagios/cgi.cfg';
}
elseif(file_exists('/etc/nagios3/cgi.cfg'))  //ubuntu/debian nagios3 installs
{
	$cgifile = '/etc/nagios3/cgi.cfg';
}
else
{
	$errors++;
	$errorstring .= "NOTICE: cgi.cfg file not found.  Please specify the location of this file in your /etc/".ETC_CONF." file\n";
	$cgifile = '';
}

define('CGICFG', $cgifile);

// nagios.cmd file
//
// Check for directory instead of file. The file is actually a named
// pipe and its existence can be transient.

if(is_dir('/usr/local/nagios/var/rw'))
{
	$nagcmd = '/usr/local/nagios/var/rw/nagios.cmd';  //source install
}
elseif(is_dir('/var/nagios/rw'))  //yum install
{
	$nagcmd = '/var/nagios/rw/nagios.cmd';
}
elseif(file_exists('/var/spool/nagios/cmd'))  // centos 6.5 yum package install
{
	$nagcmd = '/var/spool/nagios/cmd/nagios.cmd';
}
elseif(is_dir('/var/lib/nagios3/rw'))  //ubuntu/debian nagios3
{
	$nagcmd = '/var/lib/nagios3/rw/nagios.cmd';
}
else
{
	$errors++;
	$errorstring .= "NOTICE: nagios.cmd file not found.  Please specify the location of this file in your /etc/".ETC_CONF." file\n";
	$nagcmd = '';
}

define('NAGCMD', $nagcmd);


////////////////////////////// VShell conf file  //////////////////////////////

echo "Creating vshell configuration file...\n";

$output = system('/bin/touch /etc/'.ETC_CONF, $code);
if($code > 0){
	$errors++;
	$errorstring .= "ERROR: Failed to create config/".ETC_CONF." file in /etc directory \n$output\n";
}else{
	// Try to create from template file, fall back on default file
	ob_start();
	include(dirname(__FILE__).'/config/vshell.conf.template');
	$conf = ob_get_clean();
	if( ! file_put_contents('/etc/'.ETC_CONF, $conf) ){
		$errors++;
		$errorstring .= "ERROR: Failed to create config/".ETC_CONF." file from template.";
		$errorstring .= "Installing default file instead. Manually check /etc/".ETC_CONF." values are correct\n";
		$output = system('/bin/cp config/vshell.conf.default /etc/'.ETC_CONF, $code);
		if($code > 0) {
			$errors++;
			$errorstring .= "ERROR: Failed to copy config/vshell.conf file to /etc directory \n$output\n";
		}
	}
}


////////////////////////////////// All done ///////////////////////////////////

echo "VShell Installation Script Complete!\n";
if( $errors > 0 ){
	echo "\n";
	echo "*** The following errors occured: \n";
	echo $errorstring."\n";
}
exit($errors);


/* End of file install.php */
