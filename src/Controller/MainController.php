<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use APP\Repository\OfferRepository;
use App\Entity\Offer;
use App\Form\CreateOfferFormType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{


    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }
    /**
     * @Route("/", name="homepage")
     */
    public function index(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
      $repository = $em->getRepository(Offer::class);

      $offers = $repository->findAll();
      if (!$offers) {
        throw $this->createNotFoundException(('No Offer'));
        return $this->render('main/index.html.twig', [
          'offers' => $offers,
          'controller_name' => 'MainController',
        ]);
      }
      $pagination = $paginator->paginate(
        $offers, // Requête contenant les données à paginer (ici nos articles)
        $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
        2 // Nombre de résultats par page
    );
      return $this->render('main/index.html.twig', [
        'offers' =>$offers,
        'pagination' => $pagination,
        'controller_name' => 'MainController',
      ]);

    }
    

    /**
     * @Route("/manage_offer", name="manage_offer")
     */
    public function manage_offer(): Response
    {
        return $this->render('main/manage_offer.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

    /**
     * @Route("/create_offer", name="create_offer")
     */
    public function add(Request $request, ): Response
    {
        $Offer = new Offer();
        $form = $this->createForm(CreateOfferFormType::class, $Offer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($Offer->getDate()) {
                $Offer->setDate(new \DateTime());
            }
            if (null !== $Offer->getImage()) {
                $file = $form->get('Image')->getData();
                $fileName = uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'), // Le dossier dans le quel le fichier va etre charger
                      $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $Offer->setImage($fileName);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($Offer);
            $entityManager->flush();
            return new RedirectResponse($this->urlGenerator->generate('success'));
        }

        return $this->render('main/create_offer.html.twig', [
            'controller_name' => 'MainController',
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/success", name="success")
     */
    public function CreationSuccess(): Response
    {
        return $this->render('main/success.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }
}
