# OAuth

* Version: 0.2.0
* Author: Jon Mifsud <http://jonmifsud.com>
* Build Date: 2012-06-04
* Requirements: Symphony 2.6.x

## Installation

1. Download the OAuth extension and upload the `oauth` folder to the `extensions` folder.
2. Enable the extension by selecting `OAuth` in the list and choose `Enable` from the with-selected menu, then click Apply.
3. For Any oAuth that you intend to use add in the Application Code and Secret; and if required you can also add a scope
4. If you would like oAuth to be used as a main-login select an oAuth Provided to be used as main - this will output an event showing if the user is logged in.

Note: If you are using the Member's extension, you do not need to set the `main-login` above but users will be automatically linked to a logged in member or a new entry is created for that user. In case of different oauth accounts with the same email address, they will also be linked.

## Usage

Once you Install the extension you would need to add the `oAuth` event to a Symphony page which will take care of Autentification/Authorization. TODO this has to be a param

In the case where you have a 'main' provider `/data/events/oauth/url` should contain the URL where you should redirect the person to log-in. Or to be provided in a link (button)
Once Authorized the user will be redirected to your Authorization page which will log-in the user and redirect to a predifined page.

Alternatively you would need to add a DataSource to the page - first proceed by creating a DataSource of Type oAuth, then choose the provider you would like to use. 
You would find the url in a similar manner as for the main but rather then being in Events it would be under your datasource.

When Verified the XML Element would have two parameters `logged-in` reading `yes` (if not logged-in says `no`) as well as a `token` which represnts the valid token given to the user.

If you would like to use data from these social networks - you are free to create datasource of type 'oAuth Remote Datasource', 
these allow you to specify the Provider you want to use, the path to pass to the oAuth request and the xPath of the output that you want visible in your XML

## Using oAuth with Members

For each oAuth with which you want to support `auto registration` you will need to add an XSLT file for mapping the members data to the symphony fields. Below is an example for facebook oAuth, which is placed inside `workspace/oauth/facebook.xsl`, the node names are to match the field handles of the section in symphony, and the node values are the values you want to insert inside the members section. The data available under /data/entry varies depending on the oAuth channel and scopes which you have access to. Facebook requires additional scope request to obtain an email address.

	<?xml version="1.0" encoding="UTF-8"?>
	<xsl:stylesheet version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml"
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
		omit-xml-declaration="yes"
		encoding="UTF-8"
		indent="yes" />

	<xsl:template match="/">
		<member>
			<first-name>
				<xsl:value-of select='/data/entry/first-name'/>
			</first-name>
			<last-name>
				<xsl:value-of select='/data/entry/last-name'/>
			</last-name>
			<email>
				<xsl:value-of select='/data/entry/email'/>
			</email>
			<role>public</role>
		</member>
	</xsl:template>

	</xsl:stylesheet>


## Important

Note that currently the event has to be attached to an actual page - at the moment this has to be `authorize` = available through `http://yoursite.com/authorize` and
must also have a url parameter named `source`. Whilst this is mandatory for now - the plan is to move this into preferences allowing the user to specify. Note that this
is still in experimental stage and might not work 100% as exptected.

## Known Bugs

1. Currently there is an issue with logging out of Users. The session seems to not expire. This should be fixed soon along anything else that might be noticed.

2. Currently oAuth only supports the use of secondary logins when there is already a Main Provider (to augment data/logins) in the future might offer the option 
to login with any system and still consider the user to have a valid login.

## Roadmap

Support Additional oAuth Providers as well as events to push data and a tutorial how to create custom events/datasources using oAuth Libraries provided along this extension

## Version History

### 0.1.2

* initial release of this extension