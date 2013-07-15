# MAKE PFSENSE CAPTIVE PORTAL WEBAPI COMPATIBLE

## PFSENSE MACHINE

- VBoxManage convertfromraw pfSense-x.x.x-RELEASE-4g-i386-nanobsd_vga-xxxxxxxx-xxxx.img pfSense-x.x.x-RELEASE-4g-i386-nanobsd_vga-xxxxxxxx-xxxx.vdi
- install pfsense
- user: admin, password: pfsense
- 2 interfaces:
  - bridged -> em0 -> wan
  - host-only -> em1 -> lan
  - no vlan setup
  - let pfsense configure environment during installation of virtual machine
  - when set up, start console
    - dhclient em1
    - use IP on em1 to configure default route in TEST MACHINE
  - cd /; tar cvfz pfsense.tgz *
  - scp pfsense.tgz me@mylocalmachine:/some/path/


## TEST MACHINE

- install ubuntu on virtual box
- user: ubuntu, password: reverse
- 1 interface:
  - host only-adapter
  - network: vboxnet0
  - ip via dhcp
  - default route to host only ip of pfsense machine


## WHAT TO DO

NOTE: before doing any changes to the code, please disable whitespace fixing, because
`patch` is going to have a hard time mixing those in.

- unpack
- change code
- copy the files that were changed back to the pfsense virtual machine
- during my changes these files/folders needed to be copied:
  - /etc/inc/auth.inc
  - /etc/inc/captiveportal.inc
  - /etc/inc/JSON.php
  - /etc/inc/Requests
  - /usr/local/captiveportal/index.php
  - /usr/local/www/services_captiveportal.php
- enable captive portal in the web interface http://ip-of-the-host-only-adapter/
- check on the TEST MACHINE if it works
