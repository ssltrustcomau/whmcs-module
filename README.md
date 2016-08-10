[SSLTrust](https://www.ssltrust.com.au) Reseller Partner Program WHMCS Module

INSTALL
------

upload the contents of modules folder to your WHMCS modules folder ( in the root of where you installed WHMCS )


Once uploaded login to your WHMCS and go to Setup > Addon Modules

In the add ons list find Keyko-Admin and Activate it.

Now click configure and enter your API Key and Secrete Key we provided to you when you joined the program.


And give access control to whoever you want to be able to access it.


You will now have a new menu item up the top: Addons > Keyko-Admin

this page will list all the products available through the program.


ADDING PRODUCTS
------
Go to Setup > Products/Services > Products/Services

And Create a New Product

- Enter all your new products details
- Set the Welcome Email to None
- Set Require Domain to not ticked ( optional )

Under Module Settings Select Keyko

- Now you can select the Certificate Type.
- You can select the months or leave it as default for the lowest amount of months to be auto selected per what is available on the product.
- If you tick Sandbox then all the orders made for this product will go through our testing ordering system and you can see how the process works for ordering and configuring the SSL Certificates to get it issued.

If you want you can also have configurable options for your clients to select the months themselves. Just name the option Months


ORDER PROCESS
------
So when a custom of yours order through your WHMCS and it is activated (payed for or manually activated by you) it will send a request to the API to create a new order under your account with us. If you have any account credit it will auto deduct the amount and issue the certificate to your customer. And send them an email for them to configure their new SSL in the white labeled system
If you do not have any account credit for it to auto deduct the amount, then you will need to pay the invoice in your account for the new SSL Certificate to be issued to your customer and for them to start configuring it.
If you save a credit card in your Account and set you payment method to Credit Card in your  Account > My Details page. Then we will attempt to charge your card, and if successful we will auto issue the new SSL Certificate to your customer.


SUPPORT
------
Please open a support ticket from http://support.ssltrust.com.au or email support@ssltrust.com.au
