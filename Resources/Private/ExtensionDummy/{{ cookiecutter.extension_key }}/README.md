# {{ cookiecutter.object_name|upper }}-EXTENSION

This extension is based on the **[rampage](../rampage/README.md)** extension to create {{ cookiecutter.object_name|lower }} objects that can be managed in TYPO3 with all the advantages and functions of normal pages

## Quick installation

1. Install the extension by `composer req {{ cookiecutter.vendor_name|lower }}/{{ cookiecutter.extension_key.replace('_', '-') }}`.
2. Adjust the registration setup in [ext_localconf.php](ext_localconf.php) (if you want that).
3. Create a new page of type "{{ cookiecutter.object_name|capitalize }}-Category" (doktype: {{ cookiecutter.category_doktype }}).
4. All pages inside this category page are automatically treated as {{ cookiecutter.object_name|lower }} objects.
5. Display {{ cookiecutter.object_name|lower }} properties on all {{ cookiecutter.object_name|lower }} pages by using the following TypoScript:

```typo3_typoscript
page.16848430{{ cookiecutter.category_doktype }} = USER
page.16848430{{ cookiecutter.category_doktype }} {
  userFunc = Zeroseven\Rampage\Utility\RenderUtility->renderUserFunc
  file = EXT:{{ cookiecutter.extension_key }}/Resources/Private/Templates/Info.html
  registration = {{ cookiecutter.extension_key }}
}
```

## More information

Check out the **[rampage](../rampage/README.md)** extension for more information about the configuration and usage of this extension.
