# Cobot Captive Portal

To be able to use cobot via web api as authentication mechanism for pfsense captive portal, you need to follow these steps:

1. Install the "System Patches" package on pfsense on your router.
2. Go to the "Patches" page and add the following URL:
`https://github.com/upstream/cobot_captive_portal/commit/8a55dd5d7d45873b916806055869196657ef5b6c`
3. Fetch and apply the patch.
4. Go to the "Captive Portal" page, enable it, choose "Web API" as authentication mechanism and enter the URL to the API.