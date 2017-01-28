<?php

	Class OauthMember extends SymphonyMember {

	/*-------------------------------------------------------------------------
		Authentication:
	-------------------------------------------------------------------------*/

		public function isLoggedIn() {

			if(self::$isLoggedIn) return true;

			$this->initialiseCookie();

			$oAuthName = $this->cookie->get('oauth');

			if ($oAuthName){
				$userId = $this->cookie->get('oauth-user-id');

				// check if this user already has an account
				$id = Symphony::Database()->fetchVar('member_id',0,"SELECT member_id FROM `tbl_oauth_token` WHERE user_id='{$userId}' AND `system` = '{$oAuthName}';");

				if (isset($id)){
					self::$member_id = $id;
					self::initialiseMemberObject();
					self::$isLoggedIn = true;

					$this->cookie->set('id', $id);
					$this->cookie->set('members-section-id', $this->getMember()->get('section_id'));

					return true;
				}
			}

			//if so far we are not logged in try using the standard member login
			return parent::isLoggedIn();

			$this->logout();

			return false;
		}

		// if logged in with oAuth show which service the user is logged in with
		public function appendLoginStatusToEventXML(array $context = null){
			parent::appendLoginStatusToEventXML($context);

			$oAuthName = $this->cookie->get('oauth');

			$memberLoginInfo = $context['wrapper']->getChildByName('member-login-info',0);

			if ($oAuthName){

				$memberLoginInfo->setAttribute('oauth-service',$oAuthName);

			} 

			$memberID = $memberLoginInfo->getAttribute('id');
			if ($memberID){

				$oAuthLogins = Symphony::Database()->fetch("SELECT * FROM tbl_oauth_token WHERE `member_id` IN ({$memberID})");

				$authenticatedWith = new XMLElement('authenticated-with');
				$memberLoginInfo->appendChild($authenticatedWith);

				foreach ($oAuthLogins as $key => $value) {
					$authenticatedWith->appendChild( new XMLElement('service',$value['system']));
				}
			}

		}

	}
