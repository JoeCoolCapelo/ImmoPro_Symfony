<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\BienRepository;
use App\Repository\DocumentRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/documents')]
class DocumentController extends AbstractController
{
    #[Route('/', name: 'documents.index', methods: ['GET'])]
    public function index(DocumentRepository $documentRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $this->denyAccessUnlessGranted('DOCUMENT_VIEW_ANY');

        $qb = $documentRepository->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('documents/index.html.twig', [
            'documents' => $pagination,
        ]);
    }

    #[Route('/', name: 'documents.store', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function store(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, BienRepository $bienRepository, TransactionRepository $transactionRepository): Response
    {
        $titre = $request->request->get('titre');
        $type = $request->request->get('type');
        $file = $request->files->get('file');

        if (!$titre || !$type || !$file) {
            $this->addFlash('error', 'Le titre, le type et le fichier sont obligatoires.');
            return $this->redirect($request->headers->get('referer'));
        }

        $bienId = $request->request->get('bien_id');
        $transactionId = $request->request->get('transaction_id');

        $document = new Document();
        $document->setTitre($titre);
        $document->setType($type);
        $document->setUser($this->getUser());

        if ($bienId) {
            $document->setBien($bienRepository->find($bienId));
        }
        if ($transactionId) {
            $document->setTransaction($transactionRepository->find($transactionId));
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $file->move(
            $this->getParameter('documents_directory'),
            $newFilename
        );

        $document->setPath('documents/'.$newFilename);

        $em->persist($document);
        $em->flush();

        $this->addFlash('success', 'Document ajouté avec succès.');
        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/{id}/download', name: 'documents.download', methods: ['GET'])]
    #[IsGranted('DOCUMENT_VIEW', subject: 'document')]
    public function download(Document $document): Response
    {
        $path = $this->getParameter('public_directory') . '/' . $document->getPath();
        
        if (!file_exists($path)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = $document->getTitre() . '.' . $extension;

        return $this->file($path, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/{id}/delete', name: 'documents.destroy', methods: ['POST'])]
    #[IsGranted('DOCUMENT_DELETE', subject: 'document')]
    public function destroy(Request $request, Document $document, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            $path = $this->getParameter('public_directory') . '/' . $document->getPath();
            if (file_exists($path)) {
                unlink($path);
            }

            $em->remove($document);
            $em->flush();

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
