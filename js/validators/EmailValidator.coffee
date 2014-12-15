class OpenclerkForms.Validators.EmailValidator
  constructor: (@message) ->

  invalid: (data, form) ->
    if data != null && !(/^[^@ ]+@([^@ ]+\.)+[^@ ]+$/.test(data))
      return [@message]
