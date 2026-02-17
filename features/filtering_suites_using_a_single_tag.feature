Feature: Filtering suites using a single tag
    In order to only run suites configured with a specific tag
    As a Behat User
    I want to run only suites with a specific tag

    Background:
        Given a Behat configuration containing:
        """
        default:
            extensions:
                SyliusLabs\SuiteTagsExtension: ~
            suites:
                first:
                    contexts:
                        - FirstContext
                    filters:
                        tags: "@first&&@joined"
                second:
                    contexts:
                        - SecondContext
                    filters:
                        tags: "@second&&@joined"
        """
        And a context file "features/bootstrap/FirstContext.php" containing:
        """
        <?php

        use Behat\Behat\Context\Context;

        class FirstContext implements Context
        {
            protected $property = 'first';

            #[\Behat\Step\When('I get the property')]
            public function iGetTheProperty()
            {
                printf('property value: %s', $this->property);
            }
        }
        """
        And a context file "features/bootstrap/SecondContext.php" containing:
        """
        <?php

        use Behat\Behat\Context\Context;

        class SecondContext extends FirstContext
        {
            protected $property = 'second';
        }
        """
        And a feature file containing:
        """
        @joined
        Feature: Default way behat works with multiple tags
            @first @second
            Scenario: Default way behat works with multiple tags
                When I get the property
        """

    Scenario: Passing a suite tag runs only suites with that tag
        When I run Behat with suite tag "@first"
        Then it should pass
        And it should have run 1 scenario
        And its output should contain "property value: first"

    Scenario: Passing a suite tag runs all suites containing that tag
        When I run Behat with suite tag "@joined"
        Then it should pass
        And it should have run 2 scenarios
        And its output should contain "property value: first"
        And its output should contain "property value: second"

    Scenario: Passing a negated suite tag runs all suites that do not contain that tag
        When I run Behat with suite tag "~@first"
        Then it should pass
        And it should have run 1 scenario
        And its output should contain "property value: second"

    Scenario: Passing a suite tag with no suites with that tag fails
        When I run Behat with suite tag "@third"
        Then it should fail
        And its output should contain "No suites left using suite tags: @third."
