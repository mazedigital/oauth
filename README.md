# OAuth

* Version: 0.1.0
* Author: Jon Mifsud <http://jonmifsud.com>
* Build Date: 2012-06-04
* Requirements: Symphony 2.3

## Installation

1. Download the OAuth extension and upload the `oauth` folder to the `extensions` folder.
2. Enable the extension by selecting `OAuth` in the list and choose `Enable` from the with-selected menu, then click Apply.
3. For Any oAuth that you intend to use add in the Application Code and Secret; and if required you can also add a scope
4. If you would like oAuth to be used as a main-login select an oAuth Provided to be used as main - this will output an event showing if the user is logged in.

## Usage

Once you Install the extension you would need to add the `oAuth` event to a Symphony page which will take care of Autentification/Authorization. TODO this has to be a param

In the case where you have a 'main' provider `/data/events/oauth/url` should contain the URL where you should redirect the person to log-in. Or to be provided in a link (button)
Once Authorized the user will be redirected to your Authorization page which will log-in the user and redirect to a predifined page.

Alternatively you would need to add a DataSource to the page - first proceed by creating a DataSource of Type oAuth, then choose the provider you would like to use. 
You would find the url in a similar manner as for the main but rather then being in Events it would be under your datasource.

When Verified the XML Element would have two parameters `logged-in` reading `yes` (if not logged-in says `no`) as well as a `token` which represnts the valid token given to the user.

If you would like to use data from these social networks - you are free to create datasource of type 'oAuth Remote Datasource', 
these allow you to specify the Provider you want to use, the path to pass to the oAuth request and the xPath of the output that you want visible in your XML

## Roadmap

Support Additional oAuth Providers as well as events to push data and a tutorial how to create custom events/datasources using oAuth Libraries provided along this extension

## Version History

### 0.1.0

* initial release of this extension