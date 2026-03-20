Feature: Not using isolation runs suites as normal
    In order to not interfere with default suite logic
    As a Behat User
    I want for suites to not be affected when isolation is not passed

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

    Scenario: Passing no isolation tag runs suites as normal
        Given a feature file containing:
        """
        @joined
        Feature: Default way behat works with multiple tags
            @first @second
            Scenario: Default way behat works with multiple tags
                When I get the property
        """
        When I run Behat
        Then it should pass
        And it should have run 2 scenarios
        And its output should contain "property value: first"
        And its output should contain "property value: second"

    Scenario: Passing no isolation tags runs specific suite as normal
        Given a feature file containing:
        """
        @joined
        Feature: Default way behat works with multiple tags
            @first @second
            Scenario: Default way behat works with multiple tags
                When I get the property
        """
        When I run Behat with suite "first"
        Then it should pass
        And it should have run 1 scenario
        And its output should contain "property value: first"

    Scenario: Passing no isolation tags runs specific tag as normal
        Given a feature file containing:
        """
        @joined
        Feature: Default way behat works with multiple tags
            @first @second
            Scenario: Default way behat works with multiple tags
                When I get the property
        """
        When I run Behat with tag "@first"
        Then it should pass
        And it should have run 2 scenarios
        And its output should contain "property value: first"
        And its output should contain "property value: second"
