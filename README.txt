*******************************************************
****             Web Signup via SOAP               ****
*******************************************************

Company: Telinta Inc.
Website: http://www.telinta.com

	======================
	==-  INTRODUCTION  -==
	======================

	This web subcription template was created to help Telinta's customer integrate PortaOne's web sign-up service 
	(provided as a part of the PortaSwitch platform) into their own web-sites.
	The script allows a customer to subscribe for DID number, purshase a calling card, create a PINSKIP number, etc., depending on the cofiguration. 
	The template is intended for admin or reseller services. 

	======================
	==-     FEATURES   -==
	======================

	* creation of an account under predefined sub-customer ('Reseller' service);
	* creation of a sub-customer and an account under it ('Reseller' service);
	* creation of a direct retail customer and an account under it (only for 'Admin' service);
	* creation of a sub-customer customer and an account under a particular reseller (only for 'Admin' service);
	* creation of accounts under a particular retail customer or sub-customer of a needed reseller (only for 'Admin' service);
	* several sources of creating account id (using DID inventory, random generating, manual entering);
	* support of creating accounts with a defined prefix ('cc', 'a', 'cb', '020' etc.);
	* support of aliases;
	* support of multiple packages;
	* easy way to configure the service with the signup wizard and a template account;
	* CAPTCHA validation and protection from bots;
	* support of promo codes;
	* support of Referral Links functionality;
	* configurable limitation of signup attempts from the same IP address;
	* responsive design.
	
	======================
	==- CONFIGURATION -==
	======================
		
	== PortaBilling configuration ==

		1. Add a new user with the access level 'Root' and marked flag 'API token access'. Credentials of this user should be used further for Signup configuration.

	== Customer's web server part ==
	
		1. Place the files on your web server.

		2. Make sure that the web server meets minimal system requirments:
			* Apache 1.x or 2.x or similar web server with PHP module enabled
			* PHP should be compiled with SOAP, JSON, GD and OpenSSL extensions. Sessions should be allowed.
			* Web server should have write permissions for the signup directory

		3. In order to configure the Signup service use the Signup wizard. It is avalable at http://<path_to_signup>/?layout=wizard
		Follow the steps to create the configuration file. When the configuration file is generated the signupwizard.php file can be deleted.
		NOTE: In order to modify the existing config file you should access Signup wizard with the same credentials that were used during initial configuration, otherwise the Signup wizard will not allow to view/modify the configuration.

 	 	4. Integrate the signup page into the existing web site.
		
	==========================
	==- AVAILABLE SERVICES -==
	==========================

	1. Selling DID Numbers using DID inventory	
	
		== PortaBilling part ==
		
		Admin Service:
			
			1. Create an owner batch. Assign all needed numbers to this batch.
		
		Reseller Service:
				
			1.   Create a Reseller for Signup.

			2    Create DID Owner Batch managed by Signup Reseller. Assign needed DIDs to this Owner Batch.

			3.   Configure E-Payments for Signup Reseller
	
	2. Selling calling cards, PINskip accounts, etc.
		
		No special configuration is needed in PortaBilling. Follow steps in Signup wizard to configure the service. 
			
	
	=====================
	==-   LICENSING   -==
	=====================
	
	All rights to this software are reserved by Telinta Inc. 
	
	=====================
	==-      NOTES    -==
	=====================
	
	* Detailed information about used XML API can be found in the following documentation:	
	https://www.telinta.com/wp-content/uploads/PortaBilling_API_MR55.pdf

			
	Please contact support@telinta.com if you have any questions. 
