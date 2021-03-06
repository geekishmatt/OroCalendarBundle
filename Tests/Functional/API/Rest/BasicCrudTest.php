<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

/**
 * The test covers basic CRUD operations with simple calendar event.
 *
 * Use cases covered:
 * - Create regular calendar event with minimal required data.
 * - Create simple event with from url encoded content.
 * - Update recurrence data of recurring calendar event changes "updatedAt" field.
 * - Delete attendee of calendar event changes "updatedAt" field.
 *
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class BasicCrudTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Create regular calendar event with minimal required data.
     *
     * Steps:
     * 1. Create regular calendar event using minimal required data in the request.
     * 2. Get created event and verify all properties in the response.
     */
    public function testCreateRegularCalendarEventWithMinimalRequiredData()
    {
        // Step 1. Create regular calendar event using minimal required data in the request.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'    => 'Regular event',
                        'start'    => '2016-10-14T22:00:00+00:00',
                        'end'      => '2016-10-14T23:00:00+00:00',
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'allDay'   => false
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            [
                'id'                       => $response['id'],
                'invitationStatus'         => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 2. Get created event and verify all properties in the response.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $this->assertResponseEquals(
            [
                'id'                       => $newEvent->getId(),
                'calendar'                 => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'parentEventId'            => null,
                'title'                    => "Regular event",
                'description'              => null,
                'start'                    => "2016-10-14T22:00:00+00:00",
                'end'                      => "2016-10-14T23:00:00+00:00",
                'allDay'                   => false,
                'attendees'                => [],
                'editable'                 => true,
                'editableInvitationStatus' => false,
                'removable'                => true,
                'backgroundColor'          => null,
                'invitationStatus'         => Attendee::STATUS_NONE,
                'recurringEventId'         => null,
                'originalStart'            => null,
                'isCancelled'              => false,
                'createdAt'                => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt'                => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
            ],
            $response
        );
    }

    /**
     * Create simple event with from url encoded content.
     *
     * Steps:
     * 1. Create regular calendar event using minimal required data in the request.
     * 2. Get created event and verify all properties in the response.
     */
    public function testCreateSimpleCalendarEventWithFormUrlEncodedContent()
    {
        $calendarId = $this->getReference('oro_calendar:calendar:foo_user_1')->getId();
        // @codingStandardsIgnoreStart
        $content = <<<CONTENT
title=Regular%20event&description=&start=2016-10-14T22%3A00%3A00.000Z&end=2016-10-14T23%3A00%3A00.000Z&allDay=false&attendees=&recurrence=&calendar=$calendarId
CONTENT;
        // @codingStandardsIgnoreEnd
        parse_str($content, $parameters);

        // Step 1. Create regular calendar event using minimal required data in the request.
        $this->restRequest(
            [
                'method'     => 'POST',
                'url'        => $this->getUrl('oro_api_post_calendarevent'),
                'server'     => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'parameters' => $parameters,
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            [
                'id'                       => $response['id'],
                'invitationStatus'         => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 2. Get created event and verify all properties in the response.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $this->assertResponseEquals(
            [
                'id'                       => $newEvent->getId(),
                'calendar'                 => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'parentEventId'            => null,
                'title'                    => "Regular event",
                'description'              => null,
                'start'                    => "2016-10-14T22:00:00+00:00",
                'end'                      => "2016-10-14T23:00:00+00:00",
                'allDay'                   => false,
                'attendees'                => [],
                'editable'                 => true,
                'editableInvitationStatus' => false,
                'removable'                => true,
                'backgroundColor'          => null,
                'invitationStatus'         => Attendee::STATUS_NONE,
                'recurringEventId'         => null,
                'originalStart'            => null,
                'isCancelled'              => false,
                'createdAt'                => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt'                => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
            ],
            $response
        );
    }

    /**
     * Update recurrence data of recurring calendar event changes "updatedAt" field.
     *
     * Steps:
     * 1. Create recurring event and save value of "updatedAt" field.
     * 2. Wait for 1 second.
     * 3. Update event and change only attribute in recurrence data.
     * 4. Get event and check the "updatedAt" value has been modified.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRecurrenceDataOfRecurringCalendarEventChangesUpdatedAtField()
    {
        // Step 1. Create recurring event and save value of "updatedAt" field.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'      => 'Recurring event',
                        'start'      => '2016-10-14T22:00:00+00:00',
                        'end'        => '2016-10-14T23:00:00+00:00',
                        'calendar'   => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'allDay'     => false,
                        'recurrence' => [
                            'timeZone'       => 'UTC',
                            'recurrenceType' => Recurrence::TYPE_DAILY,
                            'interval'       => 1,
                            'startTime'      => '2016-10-14T22:00:00+00:00',
                            'occurrences'    => 4,
                        ]
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            [
                'id'                       => $response['id'],
                'invitationStatus'         => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        $originalUpdatedAt = $newEvent->getUpdatedAt();
        $this->assertInstanceOf('DateTime', $originalUpdatedAt, 'Failed asserting "updatedAt" field was set.');

        // Step 2. Wait for 1 second.
        sleep(1);

        // Step 3. Update event and change only attribute in recurrence data.
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'recurrence' => [
                            'timeZone'       => 'UTC',
                            'recurrenceType' => Recurrence::TYPE_DAILY,
                            'interval'       => 2,
                            'startTime'      => '2016-10-14T22:00:00+00:00',
                            'occurrences'    => 4,
                        ]
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'invitationStatus'         => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 4. Get event and check the "updatedAt" value has been modified.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $newUpdatedAt = new \DateTime($response['updatedAt'], new \DateTimeZone('UTC'));

        $diffInSeconds = $newUpdatedAt->getTimestamp() - $originalUpdatedAt->getTimestamp();

        $this->assertGreaterThanOrEqual(1, $diffInSeconds, 'Failed assertic "updatedAt" was updated.');
    }

    /**
     * Delete attendee of calendar event changes "updatedAt" field.
     *
     * Steps:
     * 1. Create regular event with 2 attendees and save value of "updatedAt" field.
     * 2. Wait for 1 second.
     * 3. Update event and delete 1 attendee.
     * 4. Get event and check the "updatedAt" value has been modified.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDeleteAttendeeOfCalendarEventChangesUpdatedAtField()
    {
        // Step 1. Create regular event with 2 attendees and save value of "updatedAt" field.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'     => 'Regular event',
                        'start'     => '2016-10-14T22:00:00+00:00',
                        'end'       => '2016-10-14T23:00:00+00:00',
                        'calendar'  => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'allDay'    => false,
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email'       => 'foo_user_2@example.com',
                                'status'      => Attendee::STATUS_ACCEPTED,
                                'type'        => Attendee::TYPE_REQUIRED,
                            ],
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                                'email'       => 'foo_user_3@example.com',
                                'status'      => Attendee::STATUS_ACCEPTED,
                                'type'        => Attendee::TYPE_REQUIRED,
                            ],
                        ]
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            [
                'id'                       => $response['id'],
                'invitationStatus'         => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        $originalUpdatedAt = $newEvent->getUpdatedAt();
        $this->assertInstanceOf('DateTime', $originalUpdatedAt, 'Failed asserting "updatedAt" field was set.');

        // Step 2. Wait for 1 second.
        sleep(1);

        // Step 3. Update event and delete 1 attendee.
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email'       => 'foo_user_2@example.com',
                                'status'      => Attendee::STATUS_ACCEPTED,
                                'type'        => Attendee::TYPE_REQUIRED,
                            ],
                        ]
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'invitationStatus'         => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 4. Get event and check the "updatedAt" value has been modified.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $newUpdatedAt = new \DateTime($response['updatedAt'], new \DateTimeZone('UTC'));

        $diffInSeconds = $newUpdatedAt->getTimestamp() - $originalUpdatedAt->getTimestamp();

        $this->assertGreaterThanOrEqual(1, $diffInSeconds, 'Failed assertic "updatedAt" was updated.');
    }
}
