class OpenclerkForms.Validators.RequiredValidator
  constructor: (@message) ->

  invalid: (data, form) ->
    if !(data != null && data.length > 0)
      return [@message]