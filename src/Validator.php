<?php

namespace OpenclerkForms;

interface Validator {
  /**
   * @return an error message if the value is not valid
   */
  function invalid(Form $form, $data);

  /**
   * Get the Javascript validator code for this validator.
   */
  function getScriptValidator();
}

