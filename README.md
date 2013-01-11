## Cobot Captive Portal

This is a system patch for the [Pfsense](http://pfsense.org) router software that adds support for [Cobot](http://cobot.me) to the captive portal feature of Pfsense.

With this patch installed the captive portal will authenticate directly against the Cobot API, allowing users to log in using their Cobot account credentials.

Note that this disables support for Radius/local captive portal authentication.

***Currently this package supports version 2.1.0 of Pfsense.***

## Installation


### Install the _System Patches_ package

Install the "System Patches" package on pfsense on your router:

On the Pfsense web interface go to _System_ => _Packages_...

![Select Packages](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/packages.png)

..and go to _Available Packages_.

![Select Packages](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/available_packages.png)

Scroll down to _System Patches_ and click the _Add_ button on the right.

![Install system pactches packet](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/system_patches_packet.png)

### Apply the patch

Go to the _System_ => _Patches_ page...

![Select Patches](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/patches.png)

... and click on the _Add Patch_ button.

![Add Patch](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/add_patch.png)

Enter "Cobot" in the description field and "https://github.com/cobot/cobot_captive_portal/commit/919c69b028109d2a6b208dbe8b102c8fd9c6b752" in the "URL/Commit ID" field. Click the _Save_ button.

![Edit Patch](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/edit_patch.png)

Click _Fetch_ and then _Apply_ on the patch.

![Aply Patch](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/apply_patch.png)

### Enable the captive portal

Go to the _Services_, _Captive Portal_.

![Select captive portal](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/captive_portal.png)

Check _Enable captive portal_, select the interface you want to protect (in most cases LAN), enter the subdomain (http://_subdomain_.cobot.me) of your space under _Space subdomain_ and your access token (you can get this token on Cobot under _Setup_ => _Wifi integration_).

![Activate captive portal](https://raw.github.com/cobot/cobot_captive_portal/master/screenshots/edit_captive_portal.png)

Press "Save" and you are done.
