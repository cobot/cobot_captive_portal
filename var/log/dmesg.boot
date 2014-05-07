Copyright (c) 1992-2012 The FreeBSD Project.
Copyright (c) 1979, 1980, 1983, 1986, 1988, 1989, 1991, 1992, 1993, 1994
	The Regents of the University of California. All rights reserved.
FreeBSD is a registered trademark of The FreeBSD Foundation.
FreeBSD 8.3-RELEASE-p16 #0: Thu May  1 16:19:39 EDT 2014
    root@pf2_1_1_i386.pfsense.org:/usr/obj.i386/usr/pfSensesrc/src/sys/pfSense_wrap_vga.8.i386 i386
Timecounter "i8254" frequency 1193182 Hz quality 0
CPU: Intel(R) Core(TM) i7-3667U CPU @ 2.00GHz (2529.55-MHz 686-class CPU)
  Origin = "GenuineIntel"  Id = 0x306a9  Family = 6  Model = 3a  Stepping = 9
  Features=0x783fbbf<FPU,VME,DE,PSE,TSC,MSR,MCE,CX8,APIC,SEP,MTRR,PGE,MCA,CMOV,PAT,PSE36,MMX,FXSR,SSE,SSE2>
  Features2=0x209<SSE3,MON,SSSE3>
  AMD Features=0x8000000<RDTSCP>
  TSC: P-state invariant
real memory  = 536805376 (511 MB)
avail memory = 502190080 (478 MB)
pnpbios: Bad PnP BIOS data checksum
ipw_bss: You need to read the LICENSE file in /usr/share/doc/legal/intel_ipw/.
ipw_bss: If you agree with the license, set legal.intel_ipw.license_ack=1 in /boot/loader.conf.
module_register_init: MOD_LOAD (ipw_bss_fw, 0xc073aa90, 0) error 1
ipw_ibss: You need to read the LICENSE file in /usr/share/doc/legal/intel_ipw/.
ipw_ibss: If you agree with the license, set legal.intel_ipw.license_ack=1 in /boot/loader.conf.
module_register_init: MOD_LOAD (ipw_ibss_fw, 0xc073ab30, 0) error 1
ipw_monitor: You need to read the LICENSE file in /usr/share/doc/legal/intel_ipw/.
ipw_monitor: If you agree with the license, set legal.intel_ipw.license_ack=1 in /boot/loader.conf.
module_register_init: MOD_LOAD (ipw_monitor_fw, 0xc073abd0, 0) error 1
wlan: mac acl policy registered
kbd1 at kbdmux0
cryptosoft0: <software crypto> on motherboard
padlock0: No ACE support.
acpi0: <VBOX VBOXXSDT> on motherboard
acpi0: [ITHREAD]
acpi0: Power Button (fixed)
acpi0: Sleep Button (fixed)
Timecounter "ACPI-safe" frequency 3579545 Hz quality 850
acpi_timer0: <32-bit timer at 3.579545MHz> port 0x4008-0x400b on acpi0
pcib0: <ACPI Host-PCI bridge> port 0xcf8-0xcff on acpi0
pci_link2: BIOS IRQ 9 for 0.7.INTA does not match previous BIOS IRQ 10
pci0: <ACPI PCI bus> on pcib0
isab0: <PCI-ISA bridge> at device 1.0 on pci0
isa0: <ISA bus> on isab0
atapci0: <Intel PIIX4 UDMA33 controller> port 0x1f0-0x1f7,0x3f6,0x170-0x177,0x376,0xd000-0xd00f at device 1.1 on pci0
ata0: <ATA channel> at channel 0 on atapci0
ata0: [ITHREAD]
ata1: <ATA channel> at channel 1 on atapci0
ata1: [ITHREAD]
vgapci0: <VGA-compatible display> mem 0xe0000000-0xe07fffff irq 11 at device 2.0 on pci0
em0: <Intel(R) PRO/1000 Legacy Network Connection 1.0.6> port 0xd010-0xd017 mem 0xf0000000-0xf001ffff irq 10 at device 3.0 on pci0
em0: [FILTER]
pci0: <base peripheral> at device 4.0 (no driver attached)
pci0: <bridge> at device 7.0 (no driver attached)
em1: <Intel(R) PRO/1000 Legacy Network Connection 1.0.6> port 0xd040-0xd047 mem 0xf0820000-0xf083ffff irq 9 at device 8.0 on pci0
em1: [FILTER]
battery0: <ACPI Control Method Battery> on acpi0
acpi_acad0: <AC Adapter> on acpi0
atkbdc0: <Keyboard controller (i8042)> port 0x60,0x64 irq 1 on acpi0
atkbd0: <AT Keyboard> irq 1 on atkbdc0
kbd0 at atkbd0
atkbd0: [GIANT-LOCKED]
atkbd0: [ITHREAD]
orm0: <ISA Option ROM> at iomem 0xc0000-0xc7fff pnpid ORM0000 on isa0
sc0: <System console> at flags 0x100 on isa0
sc0: VGA <16 virtual consoles, flags=0x300>
vga0: <Generic ISA VGA> at port 0x3c0-0x3df iomem 0xa0000-0xbffff on isa0
atrtc0: <AT Real Time Clock> at port 0x70 irq 8 on isa0
ppc0: parallel port not found.
Timecounter "TSC" frequency 2529552669 Hz quality 800
Timecounters tick every 10.000 msec
IPsec: Initialized Security Association Processing.
ad0: 3805MB <VBOX HARDDISK 1.0> at ata0-master UDMA33
acd0: DVDROM <VBOX CD-ROM/1.0> at ata1-master UDMA33
Trying to mount root from ufs:/dev/ufs/pfsense0
