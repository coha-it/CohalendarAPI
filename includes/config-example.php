<?php

    // Trigger Live
    const LIVE           = 0;
    const DEV            = !LIVE;

    // URL Parts
    const PROTOCOL       = 'https://';
    const SUBDOMAIN      = 'www.';
    const DOMAIN         = 'corporate-happiness';
    const TLD            = LIVE ? '.de' : '.tk';
    const API            = '/api';

    // Build URLs
    const API_URL        = PROTOCOL.SUBDOMAIN.DOMAIN.TLD.API;
    const SHOP_URL       = PROTOCOL.SUBDOMAIN.DOMAIN.TLD;

    // Auth
    const USERNAME       = 'cohapi_1521';
    const PASSWORD       = LIVE ? 'LIVE_KEY' : 'DEV_KEY';

    // CRM
    const CRM_API_URL    = 'https://accountname.centralstationcrm.net/api/'; // people/count.json
    const CRM_API_KEY    = 'your_crm_api_key';
