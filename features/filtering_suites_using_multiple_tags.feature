Feature: Filtering suites using multiple tags
    In order to only run suites configured with specific tags
    As a Behat User
    I want for suites to be limited to specified tags

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
                third:
                    contexts:
                        - ThirdContext
                    filters:
                        tags: "@third&&@disjointed"
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
        And a context file "features/bootstrap/ThirdContext.php" containing:
        """
        <?php

        use Behat\Behat\Context\Context;

        class ThirdContext extends FirstContext
        {
            protected $property = 'third';
        }
        """
        And a feature file containing:
        """
        @joined
        Feature: Default way behat works with multiple tags
            @first @second @third @disjointed
            Scenario: Default way behat works with multiple tags
                When I get the property
        """

    Scenario: Passing a union of suite tags runs only suites with those tags
        When I run Behat with suite tags "@first&&@joined"
        Then it should pass
        And it should have run 1 scenario
        And its output should contain "property value: first"

    Scenario: Passing a list of suite tags runs all suites containing at least one of those tags
        When I run Behat with suite tags "@first,@third"
        Then it should pass
        And it should have run 2 scenarios
        And its output should contain "property value: first"
        And its output should contain "property value: third"

    Scenario: Passing a union of negated suite tags runs suites that do not contain any of the those tags
        When I run Behat with suite tags "~@disjointed&&~@second"
        Then it should pass
        And it should have run 1 scenario
        And its output should contain "property value: first"

    Scenario: Passing a union of mixed suite tags runs suites that meet the criteria
        When I run Behat with suite tags "@third&&~@joined"
        Then it should pass
        And it should have run 1 scenario
        And its output should contain "property value: third"

    Scenario: Passing a union of a specific suite tag and its negation results in a failed run
        When I run Behat with suite tags "@first&&~@first"
        Then it should fail
        And its output should contain "No suites left using suite tags: @first&&~@first."

    Scenario: Passing a union of suite tags that does not match any suite results in a failed run
        When I run Behat with suite tags "@first&&@disjointed"
        Then it should fail
        And its output should contain "No suites left using suite tags: @first&&@disjointed."

    Scenario: Passing a union of negated suite tags that exclude all the suites results in a failed run
        When I run Behat with suite tags "~@joined&&~@disjointed"
        Then it should fail
        And its output should contain "No suites left using suite tags: ~@joined&&~@disjointed."

    Scenario: Passing a union of mixed suite tags that does not match any suite tags results in a failed run
        When I run Behat with suite tags "@first&&~@joined"
        Then it should fail
        And its output should contain "No suites left using suite tags: @first&&~@joined."
