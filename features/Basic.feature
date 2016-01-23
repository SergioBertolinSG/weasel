Feature: Basic

  Scenario: Fetching an empty metric set
    When I send a GET request to "owncloud/core/aaaaaa"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    []
    """