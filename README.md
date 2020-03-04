# Connect Procus to the Drupal CMS

PUM has CMS has its own CMS that can be considered as a standalone application, that it has its own datastore. It exposes a number of webforms to the internet user, where people can apply for advice, describe to the newsletter or  volunteer. The results of this forms are stored in the CMS. This extension makes it possible to push information to the CMS for Lookup tables. It also reads the submissions and takes action on it like creating contacts, relationships etc...

For the exchange information a webapi is defined in the specification language swagger. It defined in the following [Swagger YML file](pum-swagger.yaml).

## Push lookup tables

The CMS several lookup tables. This tables are posted to CMS from CiviCRM with the Job `PostLookups`. The tables are
* Representatives (All the reps of an active country)
* Countries (only the contries that ara marked as active acountry and have an active rep)
* Sectors
* OptionValues - generic OptionValue table of CiviCRM but only used for the *First contact with Pum*

## Process submissions

### Newsletter
* Create a contact with the newsletter fields.
* Add the group 'Coorporate Newsletter' to it.

### Ask advice
* Create the organisation that asks the advice
* Create a main contact for this organisation.
* Connect the two with a <to be defined> relationship

### Become volunteer

* Create the contact that aspires to become the volunteer.
* Create a case that starts the intake process.

# Installation

Als the functionality needed for the connections is part of this extension `nl.pum.cms`. Install this extension (unzip the code in the CiviCRM extension directory and enable it).

## Post install Configuration

Most of the work that is done by this extensions is done my jobs. When they fail, they sent a error message to the designated administrator. Create a template for this email in Message Templates ` Mailings -> Message Templates`. The template must contain the token `{cms.api_error}` where the error will be substituted. Add some explanation for the reader.

After the installation a new menu is created at `Administer -> System Settings -> CMS Drupal Api`, that can be used to reach the settings screen.

In this screen the following settings must be configured.
* Remote REST Api URL for the CMS. In this place you can decide what system is used (production,test etc..).
* Authorization token for the Remote call
* Who gets the exception Mail (Fill in the contact_id). 
* Message Template of the Error Email. Pick the template you have already created.

## How to use
The following jobs are added to the CiviCRM Jobs:
* DrupalCms: Getsubmissions (Always): Get information from Drupal CMS an process it in CiviCRM.
* DrupalCms: PostLookups (Hourly): Post lookup tables to the DrupalCMS with rest call.
* DrupalCms: Remove (Daily) Removes the subscriptions from the CMS.
When testing these jobs can be executed manually. In productions they must be enabled.

## Logging
The processing uses a intermediate tale `pum_cms_submission`. It has the following columns:

* `id` technical key.
* `entity` submission entity that is read. At the moment NewsLetterSubscription, ClientRegistration and ExpertApplication.
* `state` : N=Not Processed, P=Processed, F=Failure,D=Deleted (from the CMS site)
* `submission` : the submission from the CMS site in JSON format.
* `is_mailed` : is the failure already reported by email,
* `failure` : if a submission could not be processed because of an failure it can be found here.
 
 ### Known Issues
 Test versions of the CMS contained basic authentication. 
 Its possible to configure this in the settings screen by setting the literal `authorization: Basic` header. 
 You can calculate this header using the [Blitter site](https://www.blitter.se/utils/basic-authentication-header-generator/)
 In the production situation an alternative mechanism is in place and this header is ignored.






