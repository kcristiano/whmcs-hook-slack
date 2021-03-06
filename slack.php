<?php
/**
 * Slack notification hook
 *
 * @package    Slack
 * @author     Ilham Rizqi Sasmita <irs@sandiloka.com>
 * @copyright  Copyright (c) Ilham Rizqi Sasmita
 * @license    MIT License
 * @version    $Id$
 * @link       https://github.com/ilhamrizqi/whmcs-hook-slack
 */



if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function get_client_name($clientid)
{
    $json = file_get_contents(dirname(__FILE__) ."/slack.json");
    $config = json_decode($json, true);
    $adminuser = $config['adminuser'];

    $client = "";
    $command = "getclientsdetails";
    $values["clientid"] = $clientid;
    $values["pid"] = $pid;

    $results = localAPI($command,$values,$adminuser);

    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, $results, $values, $tags);
    xml_parser_free($parser);

    $data = array();
    if($results["result"] == "success")
    {
        $client = $results["firstname"]." ".$results["lastname"];
        $client = trim($client);
        $company = $results["companyname"];
        if($company != "")
        {
            $client .= " (".$company.")";
        }
    }
    else
    {
        $client = "Error";
    }

    return $client;
}

function slack_post($text)
{
    $json = file_get_contents(dirname(__FILE__) ."/slack.json");
    $config = json_decode($json, true);
    $url = $config['hook_url'];
    $text = htmlspecialchars_decode($text, ENT_QUOTES | ENT_NOQUOTES);
    $payload = array
    (
        "text"          => htmlspecialchars($text),
        "username"      => $config["username"],
        "icon_emoji"    => $config["emoji"],
        "channel"       => $config["channel"]
    );

    $data = json_encode($payload);
    logActivity("Send slack notification:".$text);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data))
    );
    $result = curl_exec($ch);
}

function hook_slack_ticketopen($vars)
{

    $ticketid = $vars['ticketid'];
    $userid = $vars['userid'];
    $deptid = $vars['deptid'];
    $deptname = $vars['deptname'];
    $subject = $vars['subject'];
    $message = $vars['message'];
    $priority = $vars['priority'];
    $name = get_client_name($userid);

    $text  = "[ID: ".$ticketid."] ".$subject." ";
    $text .= "Ticket Opened By User: ".$name." ";
    $text .= "Department: ".$deptname."\r\n\r\n";
    //$text .= "Priority: ".$priority."\r\n";
    //$text .= $message."\r\n";
    $text .= "https://tadpole.cc/pad/admin/supporttickets.php?action=view&id=" . $ticketid;

    slack_post($text);
}

function hook_slack_ticketuserreply($vars)
{
    $ticketid = $vars['ticketid'];
    $userid = $vars['userid'];
    $deptid = $vars['deptid'];
    $deptname = $vars['deptname'];
    $subject = $vars['subject'];
    $message = $vars['message'];
    $priority = $vars['priority'];
    $name = get_client_name($userid);

    $text  = "[ID: ".$ticketid."] ".$subject." ";
    $text .= "Reply From User: ".$name." ";
    $text .= "Department: ".$deptname."\r\n\r\n";
    //$text .= "Priority: ".$priority."\r\n";
    //$text .= $message."\r\n";
    $text .= "https://tadpole.cc/pad/admin/supporttickets.php?action=view&id=" . $ticketid;

    slack_post($text);
}

function hook_slack_ticketadminreply($vars)
{
    $ticketid = $vars['ticketid'];
    $admin = $vars['admin'];
    $deptid = $vars['deptid'];
    $deptname = $vars['deptname'];
    $subject = $vars['subject'];
    $message = $vars['message'];
    $priority = $vars['priority'];
    $ticketlink = $vars['tid'];

    $text  = "Admin Reply\r\n[ID: ".$ticketid."] ".$subject." ";
    $text .= "By Admin: ".$admin." ";
    $text .= "Department: ".$deptname."\r\n\r\n";
    //$text .= "Priority: ".$priority."\r\n";
    //$text .= $message."\r\n";
    $text .= "https://tadpole.cc/pad/admin/supporttickets.php?action=view&id=" . $ticketid;

    slack_post($text);
}

add_hook("TicketOpen",      1, "hook_slack_ticketopen");
add_hook("TicketUserReply", 1, "hook_slack_ticketuserreply");
add_hook("TicketAdminReply", 1, "hook_slack_ticketadminreply");
