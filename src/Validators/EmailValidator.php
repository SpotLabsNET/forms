<?php

namespace OpenclerkForms\Validators;
use OpenclerkForms\Validator;
use OpenclerkForms\Form;

class EmailValidator implements Validator {
  function __construct($message) {
    $this->message = $message;
  }

  function invalid(Form $form, $data) {
    if (is_valid_email($data)) {
      return array();
    } else {
      return array($this->message);
    }
  }

  function getScriptValidator() {
    return array("OpenclerkForms.EmailValidator", $this->message);
  }
}
