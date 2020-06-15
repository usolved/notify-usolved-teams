# notify_usolved_teams

## Overview

This plugins sends monitoring notifications to a Microsoft Teams channel. You can define and customize the information that will be sent to the MS Teams channel. This plugin can be added to monitoring solutions like Icinga or Nagios. Generally speaking, the plugin can be used anywhere where direct execution of notification scripts is possible.


## Installation

### Add the script to your monitoring plugin folder

Just download and copy the file *notify_usolved_teams.php* into your Nagios/Icinga plugin directory. For example to */usr/local/nagios/libexec/*.

Add execution permission for your monitoring user on the file check_usolved_disks.php. If you have at least PHP 5.6 or above this plugin should run out-of-the-box. We know that there are monitoring systems out there that are never updated. That's why there's still support such an old PHP version ;).


### Create a MS Teams channel Webhook URL

Click on the three dots or right click the channel you like to get the notification pushed in. Go to the menu item *Connectors* and then configure and add *Incoming Webhook*. A unique Webhook URL will be created that you need to use as a parameter in the plugin configuration.


## Usage

### Test from command line

If you are in your plugin directory execute this command:
```
./notify_usolved_teams.php --url="https://outlook.office.com/webhook/YOURCODE1/IncomingWebhook/YOURCODE2" --title="Host SERVERNAME is DOWN" --subtitle="01-01-1970 07:40:00" --message="Type{:}PROBLEM{|}Host{:}SERVERNAME{|}State{:}DOWN{|}Info{:}CRITICAL - 1.1.1.1: Host unreachable @ 1.1.1.1. rta nan, lost 100%" --link="https://monitoring.domain.com/index.php?host_name=SERVERNAME" --state="DOWN"
```

The plugins returns the exit code 0 if everyhing went fine and 2 if an issue occured. Check with *echo $?* after executing the command. 


### Arguments

You can use these arguments to configure the output of the plugin.

```
--url="<webhook url>"
This is the url you have to create and paste from your MS Teams channel

--title="<card title>"
This is the headline of the message card

[--subtitle="<card subtitle>"]
Optional: This is the sub headline of the message card

--message="<table with additional info>"
The format should be: TITLE1{:}VALUE{|}TITLE2{:}VALUE{|}TITLE3{:}VALUE

[--link="<optional link to the monitoring system>"]
Optional: Depending on your monitoring system you can give a url to the specific host or service to directly go to the issue by clicking a button

[--state="<host or service state to show a colored icon>"]
Optional: Use macro \$HOSTSTATE\$ or \$SERVICESTATE\$ in your command
```


## Configuration

In order to use the notification plugin you have to create the command and assign it to a contact or contact template. 

### Create notification commands

You are completely free to customize the content of the notification. Add the webhook url, state, card title and a messages as required arguments. The other arguments are optional. The link in the following example would be suitable for the monitoring system called Centreon to call up the right service or host directly via a button in Teams. You can also build your own URL if you have access to some custom macros to rebuild URLs from your monitoring web interface.

Host notification in commands.cfg:
```
define command {
    command_name                   host-notify-by-teams 
    command_line                   $USER1$/notify_usolved_teams.php --url="$CONTACTPAGER$" --title="Host $HOSTNAME$ is $HOSTSTATE$" --subtitle="$DATE$ $TIME$" --message="Type{:}$NOTIFICATIONTYPE${|}IP{:}$HOSTADDRESS${|}Info{:}$HOSTOUTPUT$" --link="https://yourmonitoringurl/centreon/main.php?p=20202&o=hd&host_name=$HOSTNAME$" --state="$HOSTSTATE$" 
}
```

Service notification in commands.cfg:

```
define command {
    command_name                   service-notify-by-teams 
    command_line                   $USER1$/notify_usolved_teams.php --url="$CONTACTPAGER$" --title="Service $SERVICEDESC$ on $HOSTNAME$ is $SERVICESTATE$" --subtitle="$DATE$ $TIME$" --message="Type{:}$NOTIFICATIONTYPE${|}IP{:}$HOSTADDRESS${|}Info{:}$SERVICEOUTPUT$ $LONGSERVICEOUTPUT$" --link="https://yourmonitoringurl/centreon/main.php?p=20201&o=svcd&host_name=$HOSTNAME$&service_description=$SERVICEDESC$" --state="$SERVICESTATE$" 
}
```


### Create a contact template

This is an example for a contact template that uses the service and host notification command we defined above.

```
define contact {
    name                           contact-MSTEAMS-24x7-Down-Critical 
    alias                          contact-MSTEAMS-24x7-Down-Critical 
    host_notification_period       24x7 
    service_notification_period    24x7 
    host_notification_options      d,r 
    service_notification_options   c,r 
    register                       0 
    timezone                       :Europe/Berlin 
    host_notifications_enabled     1 
    service_notifications_enabled  1 
    host_notification_commands     host-notify-by-teams 
    service_notification_commands  service-notify-by-teams 
    use                            contact-generic 
}
```


### Create a contact and use the template above

In the host and service notification command we used the macro $CONTACTPAGER$. This variable will be filled from the pager information given in this contact. You don't need to use this macro (or can use another macro) but it's quite handy when you plan to send notifications to different channels.

```
define contact {
    contact_name                   MS-Teams-Notify 
    alias                          MS-Teams-Notify 
    email                          someemailaddress
    pager                          https://outlook.office.com/webhook/YOURCODE1/IncomingWebhook/YOURCODE2 
    register                       1 
    timezone                       :Europe/Berlin 
    use                            contact-MSTEAMS-24x7-Down-Critical 
}
```


## Authors

Ricardo Klement ([www.usolved.net](http://usolved.net))