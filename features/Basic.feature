Feature: Basic

  Scenario: Fetching an empty metric set
    When I send a GET request to "example/repo/aaaaaa"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    []
    """

  Scenario: Post a metric set
    Given I add "content-type" header equal to "application/json"
    When I send a POST request to "example/repo/aaaaaa" with body:
    """
    {
      "metrics": {
        "queries": {
          "filecache": {
            "SELECT": 1234,
            "UPDATE": 12,
            "INSERT": 3,
            "DELETE": 2
          }
        },
        "performance": [
          {
            "value": 1234.5,
            "unit": "ms",
            "cardinality": 1000,
            "type": "get"
          }
        ]
      },
      "environment": {
        "php": "7.0.2"
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON node "created_at" should exist
    And the JSON node "metrics" should exist
    And the JSON node "environment" should exist
