Feature:
  In order to debug the failing tides
  As a developer
  I need to be able to see detailed logs of the river

  Scenario: See the title of the started task
    When a tide is started with a web-hook task called "my_web_hook_task"
    Then a 'Calling web-hook (my_web_hook_task)' log should be created
