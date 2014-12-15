class OpenclerkForms.Validators.MinLengthValidator
  constructor: (@number, @message) ->

  invalid: (data, form) ->
    if data != null && !(data.length >= @number)
      return [@message]
