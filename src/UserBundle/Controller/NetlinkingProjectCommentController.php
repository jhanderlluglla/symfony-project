<?php

namespace UserBundle\Controller;


use CoreBundle\Entity\NetlinkingProjectComments;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Form\Netlinking\NetlinkingProjectCommentType;

class NetlinkingProjectCommentController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws EntityNotFoundException
     */
    public function modifyCommentAction(Request $request)
    {
        $commentId = $request->get('commentId');

        $netlinkingComment = $this->getDoctrine()->getRepository(NetlinkingProjectComments::class)->find($commentId);

        if (is_null($netlinkingComment)) {
            throw new EntityNotFoundException("Entity not found");
        }

        $commentForm = $this->createForm(NetlinkingProjectCommentType::class, $netlinkingComment);
        $commentForm->handleRequest($request);

        if ($request->isMethod(Request::METHOD_POST) && $commentForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($netlinkingComment);
            $em->flush();

            return new JsonResponse([
                'status' => true,
                'message' => $this->get('translator')->trans('notify.submission_comment_edit', [], 'submission')
            ]);
        }


        $form = $this->renderView('netlinking/modal/edit_writer_comment.html.twig', [
            'form' => $commentForm->createView(),
            'commentId' => $commentId,
        ]);

        return new JsonResponse([
            'status' => true,
            'title' => $this->get('translator')->trans('modal.modify_comment', [], 'submission'),
            'body' => $form
        ]);
    }

    /**
     * @param Request $request
     * @param $commentId
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws EntityNotFoundException
     */
    public function deleteCommentAction(Request $request, $commentId)
    {
        $netlinkingComment = $this->getDoctrine()->getRepository(NetlinkingProjectComments::class)->find($commentId);

        if(is_null($netlinkingComment)){
            throw new EntityNotFoundException("Entity not found");
        }

        $em = $this->getDoctrine()->getManager();
        $netlinkingComment->setComment(null);
        $em->persist($netlinkingComment);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => $this->get('translator')->trans('comment_deleted', [], 'netlinking_project_comments')
        ]);
    }
}