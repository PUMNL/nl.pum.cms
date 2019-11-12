# Connect Procus to the Drupal CMS

PUM has CMS has its own CMS that can be considered as a standalone application, that it has its own datastore. It exposes a number of webforms to the internet user, where people can apply for advice, describe to the newsletter or  volunteer. The results of this forms are stored in the CMS. This extension makes it possible to push information to the CMS for Lookup tables. It also reads the submissions and takes action on it like creating contacts, relationships etc...

For the exchange information a webapi is defined in the specification language swagger. It defined in the following [Swagger YML file](pum-swagger.yaml).

## Push lookup tables


## Process submissions

### Newsletter
* Create a contact with the newsletter fields.
* Add the group 'Coorporate Newsletter' to it.

### Ask advice
* Create the organisation that asks the advice
* Create a main contact for this organisation.
* Connect the two with a <to be defined> relationship

### Become volunteer


# How to use
