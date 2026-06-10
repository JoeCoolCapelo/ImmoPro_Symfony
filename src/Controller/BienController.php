<?php

namespace App\Controller;

// Laravel: app/Http/Controllers/BienController.php

use App\Entity\Bien;
use App\Entity\BienImage;
use App\Form\BienType;
use App\Repository\BienRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/biens')]
class BienController extends AbstractController
{
    #[Route('/', name: 'biens.index', methods: ['GET'])]
    public function index(Request $request, BienRepository $bienRepository, PaginatorInterface $paginator): Response
    {
        $qb = $bienRepository->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC');

        $user = $this->getUser();
        if ($user) {
            if ($this->isGranted('ROLE_ADMIN')) {
                // L'admin voit tout
            } elseif ($this->isGranted('ROLE_PROPRIETAIRE')) {
                $qb->andWhere('b.owner = :user')
                   ->setParameter('user', $user);
            } elseif ($this->isGranted('ROLE_AGENT')) {
                $qb->andWhere('b.statut = :publie OR b.agent = :user')
                   ->setParameter('publie', 'publié')
                   ->setParameter('user', $user);
            } elseif ($this->isGranted('ROLE_CLIENT')) {
                // Les clients voient uniquement les biens publiés et ceux en location
                $qb->andWhere('b.statut IN (:statuts)')
                   ->setParameter('statuts', ['publié', 'loué']);
            } else {
                $qb->andWhere('b.statut = :publie')
                   ->setParameter('publie', 'publié');
            }
        } else {
            $qb->andWhere('b.statut = :publie')
               ->setParameter('publie', 'publié');
        }

        // Search and Filters
        if ($search = $request->query->get('search')) {
            $qb->andWhere('b.titre LIKE :search OR b.ville LIKE :search OR b.adresse LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($statut = $request->query->get('statut')) {
            $qb->andWhere('b.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($type = $request->query->get('type')) {
            $qb->andWhere('b.type = :type')
               ->setParameter('type', $type);
        }

        if ($nature = $request->query->get('nature')) {
            $qb->andWhere('b.nature = :nature')
               ->setParameter('nature', $nature);
        }

        if ($prixMin = $request->query->get('prix_min')) {
            $qb->andWhere('b.prix >= :prixMin')
               ->setParameter('prixMin', $prixMin);
        }

        if ($prixMax = $request->query->get('prix_max')) {
            $qb->andWhere('b.prix <= :prixMax')
               ->setParameter('prixMax', $prixMax);
        }

        if ($surfaceMin = $request->query->get('surface_min')) {
            $qb->andWhere('b.surface >= :surfaceMin')
               ->setParameter('surfaceMin', $surfaceMin);
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('biens/index.html.twig', [
            'biens' => $pagination,
        ]);
    }

    #[Route('/create', name: 'biens.create', methods: ['GET', 'POST'])]
    #[IsGranted('BIEN_CREATE')]
    public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        $bien = new Bien();
        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bien->setOwner($this->getUser());

            if ($this->isGranted('BIEN_VALIDATE')) {
                $bien->setStatut('publié');
                $bien->setAgent($this->getUser());
            } else {
                $bien->setStatut('en_attente');
            }

            $em->persist($bien);

            $imageFiles = $form->get('images')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $index => $imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('biens_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // handle exception
                    }

                    $bienImage = new BienImage();
                    $bienImage->setBien($bien);
                    $bienImage->setPath('biens/'.$newFilename);
                    $bienImage->setIsMain($index === 0);
                    $em->persist($bienImage);
                }
            }

            $em->flush();

            if ($bien->getStatut() === 'en_attente') {
                $admins = $userRepository->createQueryBuilder('u')
                    ->andWhere('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_ADMIN%')
                    ->getQuery()
                    ->getResult();

                foreach ($admins as $admin) {
                    $notif = new \App\Entity\Notification();
                    $notif->setUser($admin);
                    $notif->setTitle('Nouveau bien soumis');
                    $notif->setMessage(sprintf('Le propriétaire %s a soumis le bien "%s" pour validation.', $this->getUser()->getName(), $bien->getTitre()));
                    $notif->setType('bien_soumis');
                    $notif->setRelatedId($bien->getId());
                    $em->persist($notif);
                }
            }

            $msg = 'Bien créé avec succès. ' . ($bien->getStatut() === 'en_attente' ? 'Il est en attente de validation.' : '');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('biens.show', ['id' => $bien->getId()]);
        }

        return $this->render('biens/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'biens.show', methods: ['GET'])]
    #[IsGranted('BIEN_VIEW', subject: 'bien')]
    public function show(Bien $bien, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        // Seules les vues des vrais CLIENTS comptent (pas les agents ni les admins)
        if ($user && in_array('ROLE_CLIENT', $user->getRoles())) {
            $bien->setVues(($bien->getVues() ?? 0) + 1);
            $em->flush();
        }

        $agents = $userRepository->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_AGENT%')
            ->getQuery()
            ->getResult();

        $activeTransaction = null;
        if (in_array($bien->getStatut(), ['loué', 'vendu'])) {
            $activeTransaction = $em->getRepository(\App\Entity\Transaction::class)->findOneBy([
                'bien' => $bien,
                'isArchived' => false
            ]);
        }

        return $this->render('biens/show.html.twig', [
            'bien' => $bien,
            'agents' => $agents,
            'activeTransaction' => $activeTransaction,
        ]);
    }

    #[Route('/{id}/edit', name: 'biens.edit', methods: ['GET', 'POST'])]
    #[IsGranted('BIEN_UPDATE', subject: 'bien')]
    public function edit(Request $request, Bien $bien, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFiles = $form->get('images')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('biens_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // handle exception
                    }

                    $bienImage = new BienImage();
                    $bienImage->setBien($bien);
                    $bienImage->setPath('biens/'.$newFilename);
                    
                    $hasMain = false;
                    foreach ($bien->getImages() as $img) {
                        if ($img->isMain()) $hasMain = true;
                    }
                    $bienImage->setIsMain(!$hasMain);
                    $em->persist($bienImage);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Bien mis à jour avec succès.');

            return $this->redirectToRoute('biens.show', ['id' => $bien->getId()]);
        }

        return $this->render('biens/edit.html.twig', [
            'bien' => $bien,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/publier-tout', name: 'biens.publier_tout', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function publierTout(Request $request, BienRepository $bienRepository, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $agentId = $request->request->get('agent_id');
        $agent = $agentId ? $userRepository->find($agentId) : $this->getUser();

        $biensEnAttente = $bienRepository->findBy(['statut' => 'en_attente']);
        $count = count($biensEnAttente);

        foreach ($biensEnAttente as $bien) {
            $bien->setStatut('publié');
            $bien->setAgent($agent);

            if ($bien->getOwner()) {
                $notif = new \App\Entity\Notification();
                $notif->setUser($bien->getOwner());
                $notif->setTitle('Bien validé et publié');
                $notif->setMessage(sprintf('Félicitations ! Votre bien "%s" a été validé et est désormais visible. Agent assigné : %s.', $bien->getTitre(), $agent->getName()));
                $notif->setType('bien_valide');
                $notif->setRelatedId($bien->getId());
                $em->persist($notif);
            }
        }

        $em->flush();

        $this->addFlash('success', "$count bien(s) validé(s) et publié(s) avec succès.");

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/{id}', name: 'biens.destroy', methods: ['POST'])]
    #[IsGranted('BIEN_DELETE', subject: 'bien')]
    public function destroy(Request $request, Bien $bien, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bien->getId(), $request->request->get('_token'))) {
            $em->remove($bien);
            $em->flush();
            $this->addFlash('success', 'Bien supprimé avec succès.');
        }

        return $this->redirectToRoute('biens.index');
    }

    #[Route('/{id}/publier', name: 'biens.publier', methods: ['POST'])]
    #[IsGranted('BIEN_VALIDATE', subject: 'bien')]
    public function publier(Request $request, Bien $bien, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $agentId = $request->request->get('agent_id');
        $agent = $agentId ? $userRepository->find($agentId) : $this->getUser();

        $bien->setStatut('publié');
        $bien->setAgent($agent);

        if ($bien->getOwner()) {
            $notif = new \App\Entity\Notification();
            $notif->setUser($bien->getOwner());
            $notif->setTitle('Bien validé et publié');
            $notif->setMessage(sprintf('Félicitations ! Votre bien "%s" a été validé et est désormais visible dans le catalogue. Agent assigné : %s.', $bien->getTitre(), $agent->getName()));
            $notif->setType('bien_valide');
            $notif->setRelatedId($bien->getId());
            $em->persist($notif);
        }

        if ($agent && $agent !== $this->getUser()) {
            $notif = new \App\Entity\Notification();
            $notif->setUser($agent);
            $notif->setTitle('Nouveau bien assigné');
            $notif->setMessage(sprintf('L\'administrateur vous a assigné comme responsable commercial du bien "%s".', $bien->getTitre()));
            $notif->setType('bien_assigne');
            $notif->setRelatedId($bien->getId());
            $em->persist($notif);
        }

        $em->flush();

        $this->addFlash('success', 'Bien publié avec succès.');

        return $this->redirectToRoute('biens.show', ['id' => $bien->getId()]);
    }

    #[Route('/{id}/suspendre', name: 'biens.suspendre', methods: ['POST'])]
    #[IsGranted('BIEN_UPDATE', subject: 'bien')]
    public function suspendre(Bien $bien, EntityManagerInterface $em): Response
    {
        $newStatut = ($bien->getStatut() === 'suspendu') ? 'publié' : 'suspendu';
        $bien->setStatut($newStatut);
        $em->flush();

        $message = ($newStatut === 'suspendu') ? 'Le bien a été suspendu.' : 'Le bien a été remis en ligne.';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('biens.show', ['id' => $bien->getId()]);
    }

    #[Route('/{id}/rejeter', name: 'biens.rejeter', methods: ['POST'])]
    #[IsGranted('BIEN_VALIDATE', subject: 'bien')]
    public function rejeter(Bien $bien, EntityManagerInterface $em): Response
    {
        $bien->setStatut('brouillon');
        $em->flush();

        // TODO: Notification BienStatutChange
        $this->addFlash('success', 'Bien rejeté et remis en brouillon.');

        return $this->redirectToRoute('biens.show', ['id' => $bien->getId()]);
    }

    #[Route('/image/{id}', name: 'biens.destroy_image', methods: ['POST'])]
    #[IsGranted('BIEN_UPDATE', subject: 'image.getBien()')]
    public function destroyImage(Request $request, BienImage $image, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_image'.$image->getId(), $request->request->get('_token'))) {
            $path = $this->getParameter('public_directory') . '/' . $image->getPath();
            if (file_exists($path)) {
                unlink($path);
            }
            
            $em->remove($image);
            $em->flush();
            
            $this->addFlash('success', 'Image supprimée avec succès.');
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
