<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

/**
 * @Route("/event")
 */
class CalendarEventController extends Controller
{
    /**
     * @Route(name="oro_calendar_event_index")
     * @Template
     * @Acl(
     *      id="oro_calendar_event_view",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="VIEW",
     *      group_name=""
     * )
     */
    public function indexAction()
    {
        return array(
            'entity_class' => $this->container->getParameter('oro_calendar.calendar_event.entity.class')
        );
    }

    /**
     * @Route("/view/{id}", name="oro_calendar_event_view", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_calendar_event_view")
     */
    public function viewAction(CalendarEvent $entity)
    {
        $this->checkPermissionByParentCalendar($entity, 'view');

        $loggedUser = $this->get('oro_security.security_facade')->getLoggedUser();
        $canChangeInvitationStatus = $this->get('oro_calendar.calendar_event_manager')
            ->canChangeInvitationStatus(
                $entity,
                $loggedUser
            );
        return [
            'entity' => $entity,
            'canChangeInvitationStatus' => $canChangeInvitationStatus
        ];
    }

    /**
     * @Route(
     *      "/widget/info/{id}/{renderContexts}",
     *      name="oro_calendar_event_widget_info",
     *      requirements={"id"="\d+", "renderContexts"="\d+"},
     *      defaults={"renderContexts"=true}
     * )
     * @Template
     * @AclAncestor("oro_calendar_event_view")
     */
    public function infoAction(CalendarEvent $entity, $renderContexts)
    {
        $this->checkPermissionByParentCalendar($entity, 'view');

        $loggedUser = $this->get('oro_security.security_facade')->getLoggedUser();
        $canChangeInvitationStatus = $this->get('oro_calendar.calendar_event_manager')
            ->canChangeInvitationStatus(
                $entity,
                $loggedUser
            );
        return [
            'entity'         => $entity,
            'target'         => $this->getTargetEntity(),
            'renderContexts' => (bool) $renderContexts,
            'canChangeInvitationStatus' => $canChangeInvitationStatus
        ];
    }

    /**
     * This action is used to render the list of calendar events associated with the given entity
     * on the view page of this entity
     *
     * @Route("/activity/view/{entityClass}/{entityId}", name="oro_calendar_event_activity_view")
     * @AclAncestor("oro_calendar_event_view")
     * @Template
     */
    public function activityAction($entityClass, $entityId)
    {
        return array(
            'entity' => $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId)
        );
    }

    /**
     * @Route("/create", name="oro_calendar_event_create")
     * @Template("OroCalendarBundle:CalendarEvent:update.html.twig")
     * @Acl(
     *      id="oro_calendar_event_create",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="CREATE",
     *      group_name=""
     * )
     */
    public function createAction()
    {
        $entity = new CalendarEvent();

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime   = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime->add(new \DateInterval('PT1H'));
        $entity->setStart($startTime);
        $entity->setEnd($endTime);

        $formAction = $this->get('oro_entity.routing_helper')
            ->generateUrlByRequest('oro_calendar_event_create', $this->getRequest());

        return $this->update($entity, $formAction);
    }

    /**
     * @Route("/update/{id}", name="oro_calendar_event_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_calendar_event_update",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="EDIT",
     *      group_name=""
     * )
     */
    public function updateAction(CalendarEvent $entity)
    {
        $this->checkPermissionByParentCalendar($entity, 'edit');

        $formAction = $this->get('router')->generate('oro_calendar_event_update', ['id' => $entity->getId()]);

        return $this->update($entity, $formAction);
    }

    /**
     * Remove calendar event.
     *
     * @Route("/delete/{id}", name="oro_calendar_event_delete", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="oro_calendar_event_delete",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="DELETE",
     *      group_name=""
     * )
     */
    public function deleteAction(Request $request, $id)
    {
        return $this->forward(
            'OroCalendarBundle:Api/Rest/CalendarEvent:delete',
            ['id' => $id],
            array_merge($request->query->all(), ['isCancelInsteadDelete' => true, '_format' => 'json'])
        );
    }

    /**
     * @param CalendarEvent $entity
     * @param string        $formAction
     *
     * @return array
     */
    protected function update(CalendarEvent $entity, $formAction)
    {
        $saved = false;

        if ($this->get('oro_calendar.calendar_event.form.handler')->process($entity)) {
            if (!$this->getRequest()->get('_widgetContainer')) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.calendar.controller.event.saved.message')
                );

                return $this->get('oro_ui.router')->redirect($entity);
            }
            $saved = true;
        }

        return [
            'entity'     => $entity,
            'saved'      => $saved,
            'form'       => $this->get('oro_calendar.calendar_event.form.handler')->getForm()->createView(),
            'formAction' => $formAction
        ];
    }

    /**
     * Get target entity
     *
     * @return object|null
     */
    protected function getTargetEntity()
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $targetEntityClass   = $entityRoutingHelper->getEntityClassName($this->getRequest(), 'targetActivityClass');
        $targetEntityId      = $entityRoutingHelper->getEntityId($this->getRequest(), 'targetActivityId');
        if (!$targetEntityClass || !$targetEntityId) {
            return null;
        }

        return $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
    }

    /**
     * Checks access to manipulate the calendar event by it's calendar
     * todo: Temporary solution. Should be deleted in scope of BAP-13256
     *
     * @param CalendarEvent $entity
     * @param string        $action
     */
    protected function checkPermissionByParentCalendar(CalendarEvent $entity, $action)
    {
        $calendar = $entity->getCalendar();
        if (!$calendar) {
            throw $this->createNotFoundException('A system calendar event.');
        }

        if (!$this->get('oro_security.security_facade')->isGranted('VIEW', $calendar)) {
            throw $this->createAccessDeniedException(
                sprintf('You does not have no access to %s this calendar event', $action)
            );
        }
    }
}
