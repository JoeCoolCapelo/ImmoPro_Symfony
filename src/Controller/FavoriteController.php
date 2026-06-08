<?php

namespace App\Controller;

use App\Entity\Bien;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/favorites')]
#[IsGranted('ROLE_USER')]
class FavoriteController extends AbstractController
{
    #[Route('/', name: 'favorites.index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        // Les favoris sont une collection ManyToMany
        $qb = $user->getFavorites();

        // On utilise ArrayCollection dans le paginator ou on fait une query
        // KnpPaginator supporte l'itérable
        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('favorites/index.html.twig', [
            'favorites' => $pagination,
        ]);
    }

    #[Route('/{id}/toggle', name: 'favorites.toggle', methods: ['POST', 'GET'])]
    public function toggle(Bien $bien, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();

        if ($user->getFavorites()->contains($bien)) {
            $user->getFavorites()->removeElement($bien);
            $message = 'Retiré des favoris.';
            $status = 'removed';
        } else {
            $user->getFavorites()->add($bien);
            $message = 'Ajouté aux favoris.';
            $status = 'added';
        }

        $em->flush();

        if ($request->isXmlHttpRequest() || $request->getRequestFormat() === 'json') {
            return $this->json([
                'status' => $status,
                'message' => $message,
                'count' => $user->getFavorites()->count()
            ]);
        }

        $this->addFlash('success', $message);
        return $this->redirect($request->headers->get('referer'));
    }
}
