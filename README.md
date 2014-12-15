openclerk/forms
===============

A library for Form management, supporting both server-side and client-side
validation.

## Installing

Include `openclerk/forms` as a requirement in your project `composer.json`,
and run `composer update` to install it into your project:

```json
{
  "require": {
    "openclerk/forms": "dev-master"
  },
  "repositories": [{
    "type": "vcs",
    "url": "https://github.com/openclerk/forms"
  }]
}
```

Include the Javascript and Coffeescript files from `css/*` and `js/*` in your build
process. Alternatively, you can use [asset-discovery](https://github.com/soundasleep/asset-discovery)
to automatically include these files in your build.

## Features

1. Simple approach to validations
1. Extendible validators
1. Uses HTML5 inputs where possible to improve user experience on mobile
1. Validation occurs on both client and server side where possible

## Using

To create a simple signup form:

```php
<?php

class SignupPasswordForm extends \OpenclerkForms\Form {

  /**
   * Add the necessary fields and validators to the form.
   */
  function __construct() {
    $this->addText("name", "Name")->
      required("Name is required")->
      maxLength(64);

    $this->addEmail("email", "Email")->
      required("Email is required")->
      maxLength(255);

    $this->addPassword("password", "Password")->
      required("Password is required")->
      maxLength(255)->
      minLength(6);

    $this->addPassword("password2", "Confirm password")->
      required("Confirm password is required")->
      equals("password");

    $this->addSubmit("signup", "Signup");

  }

  /**
   * @return a list of errors (key => value or key => array(values))
   *      or nothing if the form validates fine
   */
  function validate($form) {
    $result = array();

    // check db
    $q = db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $q->execute(array($form['email']));
    if ($q->fetch()) {
      $result['email'] = "That email address is in use.";
    }

    return $result;
  }

  /**
   * The form has been submitted and is ready to be processed.
   * The user can be redirected from here if necessary, or
   * an exception can be thrown.
   * @return A success message if the form was successful
   * @throws Exception if the form could not be processed
   */
  function process($form) {

    $user = Users\UserPassword::trySignup(db(), $form['email'], $form['password']);
    if ($user) {
      return "Signed up successfully";
      // could also redirect here
    } else {
      throw new Exception("Could not sign up");
    }

  }


}

$form = new SignupPasswordForm();
$form->check();   // may process the form right here right now

// do other page rendering stuff...

// this renders both the HTML and the Javascript necessary to client-side validate
echo $form->render();
?>
```

## TODO

1. Tests
1. Publish on Packagist
1. More validators
1. More input types: dropdowns, selects, textareas
1. Writing custom, complex forms
1. Use templates to generate forms?
