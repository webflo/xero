# Xero Module

This module provides classes to help interface with the Xero Account SaaS product. You will need to be familiar with the [Xero API](http://developer.xero.com).

The module provides a factory class which instantiates XeroClient, an extension of Guzzle Client. This allows you to make requests to the Xero API via Guzzle. As well, all of Xero types are mapped out as a TypedData replacing the old `xero_make` system, and the raw JSON or XML can be fed into Serializer to normalize and denormalize data.

## XeroBundle

Xero module now requires [BlackOptic\XeroBundle](https://github.com/james75/XeroBundle) instead of PHP-Xero, and this can either be included by hacking Drupal core composer.json -OR- by installing [Composer Manager](http://drupal.org/project/composer_manager) patched with [#2276423: Composer Manager work flow breaks when a module defines a service from a dependency](https://www.drupal.org/node/2276423).

## Using XeroClient to fetch into TypedData manually

It is advised to use dependency injection to retrieve the `xero.client` and `serializer` services. This example assumes that this is stored in an object variable called `client` and serializer is `serializer`.

```php
  try {
    // Do Guzzle things.
    $options = array('query' => array('where' => 'Contact.FirstName = John'));
    $request = $this->client->get('Contacts', array(), $options);
    $response = $request->send();

    // Do Serializer things. The context array must have a key plugin_id with
    // the plugin id of the data type because Drupal.
    $context = array('plugin_id' => 'xero_contact');
    $contacts = $this->serializer->deserialize($response->getBody(TRUE), 'Drupal\xero\Plugin\DataType\Contact', 'xml', $context);

    // Contacts is a list item and can be iterated through like an entity or
    // other typed data.
    foreach ($contacts as $contact) {
      $mail = $contact->get('Email')->getValue();
    }
  }
  catch (RequestException $e) {
    // Do Logger things.
  }
```

## Using TypedData to post to Xero

```php
  $typedDataManager = \Drupal::typedDataManager();

  // Xero likes lists, so it's a good idea to create a the list item for an
  // equivalent xero data type using typed data manager.
  $definition = $typedDataManager->createListDataDefinition('xero_invoice');
  $invoices = $typedDataManager->create($definition, 'xero_invoice');

  foreach ($invoices as $invoice) {
    $invoice->setValue('ACCREC');
  }
```

Xero API Examples

This will show you how you can use the methods provided by this module
to interface your Drupal site with xero.com. You will need to be
familiar with the xero.com API (http://developer.xero.com).


I Queries

  The xero_query method makes the following queries to xero.com: GET and POST.

  1 Retrieve Data

    Retrieve all contacts in your organization:

    <?php
      $result = xero_query('get', 'Contacts', FALSE, FALSE, array(), 'json');
    ?>

    The first argument is straightforward.
    The second argument is the action we will be doing such as Contacts,
    Accounts, Invoices, Payments, etc...
    The third argument is an optional identifier to narrow down the query as
    seen in the xero.com developer documentation linked above.
    The fourth argument is an optional UTC timestamp: YYYY-MM-DDT00:00:00
    The fifth argument is an optional array of key=value filters corresponding
    to elements in object we're querying.
    The sixth argument is an optional string detailing what the response format
    should be such as json, xml, or pdf. You should only need this if you want
    a PDF from an invoice.

    The return value has three possible scenarios:
      1. NULL - Unable to even query xero.
      2. Array - An array with Errors returned from xero.com
      3. Array - An array of results.

    Thus the following query would narrow down the result to suppliers
    who have been modified after Midnight September 5th, 2010.

    <?php
      $result = xero_query('get', 'Contacts', FALSE, '2010-09-05T00:00:00', array('IsSupplier' => 'TRUE'), 'json');
    ?>

    You could loop through the results with:

    <?php
      foreach ($result['Contacts']['Contact'] as $contact) {
        //Do something
      }
    ?>

  2 Caching Data

    The Xero API for Drupal will keep a cache of objects if you use
    the xero_get_cache method. This is a simple way of grabbing all
    Contacts, Accounts, etc... that may be frequently used in forms.

    <?php
      $contacts = xero_get_cache('Contacts');
    ?>

    Note: at this time it is not possible to send in filtering.

  3 Posting

    You may also post data with the xero_query method.

    <?php
      $new = xero_make('contact', 'minimal');

      $result = xero_query('post', 'Contacts', FALSE, FALSE, $new);
    ?>

    The third and fourth arguments are not used.
    The fifth argument is now a valid array for the action we're
    doing. In this case, Contacts.

    You can find more information on the necessary elements to
    post new objects for an action on the xero.com developer site.

    You may also modify an existing item by passing the appropriate
    identifier as part of the array structure. You do not need to
    specify every element as elements not specified will remain the
    same.

    <?php
      $contact = xero_query('get', 'Contacts', FALSE, FALSE, array('Name' => 'Test Contact'));
      $updated = array(
        'Contact' => array(
          'ContactID' => $contact['Contacts']['Contact']['ContactID'],
          'Name' => 'New Name For Test Contact',
        ),
      );
      $result = xero_query('post', 'Contacts', FALSE, FALSE, $updated);
    ?>

    Note that at times data may not be modifiable, but the code here
    will always try to post.

II Form Helper

  The xero_form_helper method constructs Drupal Form API elements for
  various often-used items such as Contacts, Invoices, Accounts, and
  other goodies. These will use the xero_get_cache method described
  above.

  <?php
    // An autocomplete textfield for contacts.
    $form['ContactID'] = xero_form_helper('Contacts', $default_value);
  ?>

  The autocomplete matches have changed and the xero data type's label
  will be returned as part of the value of the field!

  Note that at this time it is not possible to pass in filters for
  xero_form_helper as it uses xero_get_cache.

III Xero Make

  The xero_make method constructs an empty, but valid data structure
  for a specified data type.

  <?php
    $contact = xero_make('contact', 'minimal');
  ?>

  The first argument is a valid type as defined by hook_xero_make_info().
  By default, the module includes contact, invoice, lineitem, address,
  phone, and creditnote.
  The second argument specifies the size of the data structure in
  reference to the mandatory (minimal), recommended, and optional (all)
  constraints for a data type. Not all data types use all sizes.
  Additional arguments can be passed in via an arguments array().

  <?php
    $invoice = xero_make('invoice', 'recommended', array('name', 2));
  ?>

  In this example the arguments array contains two arguments, which
  correspond to the arguments of xero_make_invoice. The return value
  will look like this. Note that 'recommended' is also passed into
  xero_make_lineitem, which is called directly from xero_make_invoice.

  <?php
  $invoice = array(
    'Invoice' => array(
      'Type' => '',
      'Contact' => array(
        'Name' => '',
      ),
      'Date' => '',
      'DueDate' => '',
      'LineAmountTypes' => '',
      'LineItems' => array(
        0 => array(
          'LineItem' => array(
            'Description' => '',
            'Quantity' => '',
            'UnitAmount' => '',
            'AccountCode' => '',
          ),
        ),
        1 => array(
          'LineItem' => array(
            'Description' => '',
            'Quantity' => '',
            'UnitAmount' => '',
            'AccountCode' => '',
          ),
        ),
      ),
    ),
  );
  ?>

IV Theming Data

  Although you're probably using Xero you may want to display data
  such as contacts, invoices, and credit notes. These php templates
  also use theme functions for line items and addresses respectively.

  <?php
    $output = theme('xero_contact', $contact);
  ?>

V PHP-Xero Library

  You may bypass all of the above and use the PHP-Xero library
  directly by using the php_xero_load method.

  <?php
    //returns a valid PHP-Xero object
    $xero = php_xero_load();

    $contacts = $xero->Contacts;

    $new = array(
      'Contact' => array(
        'Name' => 'New Contact',
      ),
    );

    $result = $xero->Contacts($new);
  ?>

VI Xero Types

  A xero type is a data type as defined by the Xero Developer API. Xero API now
  supports xero types in the following manner:

  %xero_type menu wildcard will load an associative array of information about
  a given type. Please consult xero_get_data_types() for more information as
  this does not contain all data types.

  The autocomplete path has been reduced to one path using the above wildcard
  argument. The autocomplete key is the Xero data type name and not the plural
  key. See below.

  Type information refers to either the key returned by Xero's Restful API, or
  the Drupal Xero API title/key for Form API.

    - name: The key to use for Form API.
    - title: The title to use for Form API.
    - guid: The GUID key for a Xero type.
    - label: The human-readable key for a Xero type. If empty, guid will be used.
    - plural: The plural key for a Xero type.

    Example (Contacts):

      'name' => 'Contact',
      'title' => 'Contact',
      'guid' => 'ContactID',
      'label' => 'Name',
      'plural' => 'Contacts',
