## Cobot Captive Portal

This is a system patch for the [Pfsense](http://pfsense.org) router software that adds support for [Cobot](http://cobot.me) to the captive portal feature of Pfsense.

With this patch installed the captive portal will authenticate directly against the Cobot API, allowing users to log in using their Cobot account credentials.

Note that this disables support for Radius/local captive portal authentication.

Currently this software supports version 2.1.0 of Pfsense.

## Installation

1. Install the "System Patches" package on pfsense on your router: on the Pfsense web interface go to _System_ => _Packages_ => _Available Packages_ and select _System Patches_.
2. Go to the _System_ => _Patches_ page, click on the _Add Patch_ button. Enter "Cobot" in the description field and "https://github.com/cobot/cobot_captive_portal/commit/8a55dd5d7d45873b916806055869196657ef5b6c" in the "URL/Commit ID" field. Click the _Save_ button.
3. Click _Fetch_ and then _Apply" on the patch.
4. Go to the _Services_, _Captive Portal_, check _Enable captive portal", select the interface you want to protect (in most cases LAN), enter the subdomain (http://_subdomain_.cobot.me) of your space under _Space subdomain_ and your captive portal token under _Access Token_ (you can get this token on Cobot under _Setup_ => "Wifi integration").
