<?php

/**

Copyright (c) 2011, PandaWhale, Inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

 - Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.

 - Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

 - Neither the name of PandaWhale, Inc. nor the names of its contributors may be
   used to endorse or promote products derived from this software without
   specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

require_once("BlowAuth.php");

class GoogleOAuth extends BlowAuth {

    // From http://code.google.com/apis/gdata/faq.html#AuthScopes
    const GOOGLE_ANALYTICS_API          = 'https://www.google.com/analytics/feeds/';
    const GOOGLE_BASE_DATA_API          = 'http://www.google.com/base/feeds/';
    const GOOGLE_SITES_DATA_API         = 'https://sites.google.com/feeds/';
    const GOOGLE_BLOGGER_API            = 'http://www.blogger.com/feeds/';
    const GOOGLE_BOOK_SEARCH_API        = 'http://www.google.com/books/feeds/';
    const GOOGLE_CALENDAR_API           = 'https://www.google.com/calendar/feeds/';
    const GOOGLE_CONTACTS_API           = 'https://www.google.com/m8/feeds/';
    const GOOGLE_DOCUMENTS_LIST_API     = 'https://docs.google.com/feeds/';
    const GOOGLE_FINANCE_API            = 'http://finance.google.com/finance/feeds/';
    const GOOGLE_GMAIL_ATOM_FEED        = 'https://mail.google.com/mail/feed/atom/';
    const GOOGLE_HEALTH_API             = 'https://www.google.com/health/feeds/';
    const GOOGLE_MAPS_API               = 'http://maps.google.com/maps/feeds/';
    const GOOGLE_PICASA_API             = 'http://picasaweb.google.com/data/';
    const GOOGLE_SIDEWIKI_API           = 'http://www.google.com/sidewiki/feeds/';
    const GOOGLE_SPREADSHEETS_API       = 'https://spreadsheets.google.com/feeds/';
    const GOOGLE_WEBMASTER_TOOLS_API    = 'http://www.google.com/webmasters/tools/feeds/';
    const GOOGLE_YOUTUBE_API            = 'http://gdata.youtube.com';

    private $_google_oauth_base_url = 'https://www.google.com';

    private $_google_request_token_uri  = '/accounts/OAuthGetRequestToken';
    private $_google_access_token_uri   = '/accounts/OAuthGetAccessToken';
    private $_google_authenticate_uri   = '';
    private $_google_authorize_uri      = '/accounts/OAuthAuthorizeToken';

    function __construct($consumer_key, $consumer_secret, $SCOPE_URL, $token = null, $token_secret = null) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;

        if (!is_null($token) && !is_null($token_secret)) {
            $this->token = $token;
            $this->token_secret = $token_secret;
        }

        $this->scope_url = $SCOPE_URL;

        $this->oauth_base_url = $this->_google_oauth_base_url;
        $this->api_base_url = rtrim($SCOPE_URL, '/');
        $this->request_token_url = $this->_google_oauth_base_url . $this->_google_request_token_uri;
        $this->access_token_url = $this->_google_oauth_base_url . $this->_google_access_token_uri;
        $this->authenticate_url = $this->_google_oauth_base_url . $this->_google_authenticate_uri;
        $this->authorize_url = $this->_google_oauth_base_url . $this->_google_authorize_uri;
    }

}

?>
