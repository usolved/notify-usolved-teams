#!/usr/bin/php
<?php

/*
Send monitoring notifications to a Microsoft Teams channel with this plugin.
-----------------------------------------------------------------------------

The MIT License (MIT)

Copyright (c) 2020 www.usolved.net
Published under https://github.com/usolved/notify_usolved_teams

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/


//---------------------------------------------------------------------
//----------------------------- Functions -----------------------------

//If wrong argument were given show some help information
function show_help($help_for)
{
	if(empty($help_for))
		$help_for = "";
	
	if($help_for == "ERROR_ARGUMENT_URL")
	{
		echo "Unknown - argument --url (webhook url) is required but missing\n";
		exit(3);
	}
	else if($help_for == "ERROR_ARGUMENT_TITLE")
	{
		echo "Unknown - argument --title (card title) is required but missing\n";
		exit(3);
	}
	else if($help_for == "ERROR_ARGUMENT_MESSAGE")
	{
		echo "Unknown - argument --message (table with additional info) is required but missing\n";
		exit(3);
	}
	else
	{
		echo "Usage:";
		echo "
	./".basename(__FILE__)." --url=\"<webhook url>\" --title=\"<card title>\" [--subtitle=\"<card subtitle>\"] --message=\"<table with additional info>\" [--link=\"<optional link to the monitoring system>\"] [--state=\"<optional host or service state to show a colored icon>\"]\n\n";
		
		echo "Options:";
		echo "
	./".basename(__FILE__)."\n
	--url=\"<webhook url>\"
	This is the url you have to create and paste from your MS Teams channel

	--title=\"<card title>\"
	This is the headline of the message card

	[--subtitle=\"<card subtitle>\"]
	Optional: This is the sub headline of the message card

	--message=\"<table with additional info>\"
	The format should be: TITLE1{:}VALUE{|}TITLE2{:}VALUE{|}TITLE3{:}VALUE

	[--link=\"<optional link to the monitoring system>\"]
	Optional: Depending on your monitoring system you can give a url to the specific host or service to directly go to the issue by clicking a button

	[--state=\"<host or service state to show a colored icon>\"]
	Optional: Use macro \$HOSTSTATE\$ or \$SERVICESTATE\$ in your command
	\n";

		echo "Example:";
		echo "
	./".basename(__FILE__)."  --url=\"https://outlook.office.com/webhook/FIRSTCODE/IncomingWebhook/SECONDCODE\" --title=\"Host SERVERNAME is DOWN\" --subtitle=\"01-01-1970 07:40:00\" --message=\"Type{:}PROBLEM{|}Host{:}SERVERNAME{|}State{:}DOWN{|}Info{:}CRITICAL - 1.1.1.1: Host unreachable @ 1.1.1.1. rta nan, lost 100%\" --link=\"https://monitoring.domain.com/index.php?host_name=SERVERNAME\" --state=\"DOWN\"\n\n";
		
		exit(3);
	}
}

//Send the request to the ms teams server
function send_request($url, $data)
{
	$ch 	= curl_init($url);

	# Setup request to send json via POST.
	//$payload = json_encode( array( "customer"=> $data ) );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

	# Return response instead of printing.
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	# Send request.
	$result = curl_exec($ch);
	curl_close($ch);

	if($result == "1")
		$exitcode = 0;
	else
	{
		echo "Error - Could not send the request to MS Teams\n";
		$exitcode = 2;	
	}

	return $exitcode;
}

//Build the card layout and convert it to json to prepare for the request
function build_card($arguments)
{

	if(!empty($arguments['message']))
	{
		$facts 		= array();
		$messages 	= explode("{|}", $arguments['message']);

		foreach ($messages as $message)
		{
			$info = explode("{:}", $message);

			if(isset($info[0]) && isset($info[1]))
			{
				$facts[] = array(
					'name' => $info[0], 
					'value' => $info[1],
				);			
			}

		}
	}

	if(!empty($arguments['state']))
	{
		//For Host state
		if($arguments['state'] == "UP")
			$state_icon = "🟢 "; //green
		else if($arguments['state'] == "DOWN")
			$state_icon = "🔴 "; //red
		else if($arguments['state'] == "UNREACHABLE")
			$state_icon = "🔴 "; //red

		//For Service state
		else if($arguments['state'] == "OK")
			$state_icon = "🟢 "; //green
		else if($arguments['state'] == "WARNING")
			$state_icon = "🟡 "; //yellow
		else if($arguments['state'] == "UNKNOWN")
			$state_icon = "⚪ "; //white
		else if($arguments['state'] == "CRITICAL")
			$state_icon = "🔴 "; //red		
		else
			$state_icon = "⚪ "; //white
	}
	else
	{
		$state_icon = "";
	}


	$data = array(
		"@type" => "MessageCard",
		"@context" => "http://schema.org/extensions",
		"themeColor" => "AAAAAA",
		"summary" => $arguments['title'],
		"sections" => array(
			array(
				"activityTitle" => $state_icon.$arguments['title'],
				"facts" => $facts,
				"markdown" => false,
			)
		),
	);


	if(isset($arguments['subtitle']) && !empty($arguments['subtitle']))
	{
		$data["sections"][0]["activitySubtitle"] = $arguments['subtitle'];
	}

	if(isset($arguments['link']) && !empty($arguments['link']))
	{
		$data["potentialAction"] = array(
			array(
				"@type" => "OpenUri",
				"name" => "Open Details",
				"targets" => array(
					array(
						"os" => "default",
						"uri" => $arguments['link'],
					),
				),
			),
		);
	}


	$data = json_encode($data);

	return $data;

}


//---------------------------------------------------------------------------
//----------------------------- Get arguments -----------------------------

$arguments 	= getopt("", 
	array(
		"url:", 
		"title:", 
		"subtitle::", 
		"message:", 
		"link::",
		"state::"
	)
);


if(is_array($arguments) && count($arguments) == 0)
	show_help("");

if(!isset($arguments['url']))
	show_help("ERROR_ARGUMENT_URL");

if(!isset($arguments['title']))
	show_help("ERROR_ARGUMENT_TITLE");

if(!isset($arguments['message']))
	show_help("ERROR_ARGUMENT_MESSAGE");


//---------------------------------------------------------------------------
//------------- Calls to build and send the request -------------------------

$data 		= build_card($arguments);
$exitcode 	= send_request($arguments['url'], $data);

exit($exitcode);