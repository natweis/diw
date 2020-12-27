<?php


	namespace assets\php;

	use Dbconnect;
	use Singleartwork;
	use PDO;
	use PDOException;

	class ArtworksDAO {

			//Make Singleton
			private static $instance;
			private $pdo;

			//Statements defined as constants

			const GET_ARTWORKS_BY_ID = "SELECT arw.*, ar.Firstname, ar.Lastname,  AVG(re.Rating) average, 
	       											COUNT(DISTINCT re.ReviewID) reviewsCount, re.ReviewDate, 
	       											cu.FirstName, cu.City, cu.Country, 
	       											ge.GenreName, su.SubjectName, ga.*					
	                               					FROM artworks arw, artists ar, reviews re, customers cu, 
	                               					     artworkgenres awg, genres ge, 
	                               					     artworksubjects aws, subjects su, galleries ga
	                               					WHERE arw.ArtWorkID = re.ArtWorkID
	                               					AND arw.ArtistID = ar.ArtistID
	                               					AND re.CustomerID = cu.CustomerID
	                               					AND arw.ArtworkID = awg.ArtWorkID 
	                               					AND awg.GenreID = ge.GenreID
	                               					AND arw.ArtWorkID = aws.ArtWorkID
	                               					AND aws.ArtWorkSubjectID = su.SubjectID
	                               					AND arw.GalleryID = ga.GalleryID
	                               					GROUP BY arw.ArtworkID
	                               					HAVING arw.ArtworkID =?";

	//		const GET_ARTWORKS_BY_ID_ADMIN           = "SELECT p.id, i.image_url, p.title, p.description, p.price, p.subcategory_id,
	//                                     p.visible, p.quantity, MAX(pr.percent) percent, AVG(r.rating) average
	//                                     FROM products p JOIN images i ON p.id = i.product_id
	//                                     LEFT JOIN reviews r ON p.id = r.product_id
	//								     LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                     WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                     GROUP BY p.id HAVING p.id = ?";
	//
	//		const GET_MOST_RATED_ARTWORKS           = "SELECT p.id, MIN(i.image_url) image_url, p.title, p.subcategory_id,
	//                                     p.visible, COUNT(DISTINCT r.id) reviewsCount, AVG(r.rating) average,
	//                                     p.price, MAX(pr.percent) percent,
	//                                     IF(MAX(pr.percent) IS NOT NULL,
	//                                     p.price - MAX(pr.percent)/100*p.price, p.price) price_fin
	//                                     FROM products p JOIN images i ON p.id = i.product_id
	//                                     LEFT JOIN reviews r ON p.id = r.product_id
	//                                     LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                     WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                     GROUP BY p.id HAVING p.visible = 1 AND p.subcategory_id IS NOT NULL
	//                                     ORDER BY average DESC, reviewsCount DESC LIMIT 4";
	//
	//		const GET_ARTWORKS_BY_ARTIST              = "SELECT p.id, MIN(i.image_url) image_url, p.title, p.subcategory_id,
	//                                     p.visible, COUNT(DISTINCT r.id) reviewsCount, AVG(r.rating) average,
	//                                     p.price, MAX(pr.percent) percent,
	//                                     IF(MAX(pr.percent) IS NOT NULL,
	//                                     p.price - MAX(pr.percent)/100*p.price, p.price) price_fin
	//                                     FROM products p JOIN images i ON p.id = i.product_id
	//                                     LEFT JOIN reviews r ON p.id = r.product_id
	//                                     LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                     WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                     GROUP BY p.id HAVING p.visible = 1 AND p.subcategory_id IS NOT NULL
	//                                     AND NOT p.id = ? AND p.subcategory_id = ?
	//                                     ORDER BY average DESC, reviewsCount DESC LIMIT 4";
	//
	//		const GET_MOST_RECENT_ARTWORKS          = "SELECT p.id, MIN(i.image_url) image_url, p.title, p.subcategory_id,
	//                                      p.visible, COUNT(DISTINCT r.id) reviewsCount, AVG(r.rating) average,
	//                                      p.price, MAX(pr.percent) percent, IF(MAX(pr.percent) IS NOT NULL,
	//                                      p.price - MAX(pr.percent)/100*p.price, p.price) price_fin
	//                                      FROM products p JOIN images i ON p.id = i.product_id
	//                                      LEFT JOIN reviews r ON p.id = r.product_id
	//                                      LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                      WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                      GROUP BY p.id HAVING p.visible = 1 AND p.subcategory_id IS NOT NULL
	//                                      ORDER BY p.created_at DESC, average DESC, reviewsCount DESC
	//                                      LIMIT 4";
	//
	//
	//		const SEARCH_ARTWORKS                   = "SELECT p.id, p.title, p.visible, MIN(i.image_url) image_url, p.subcategory_id,
	//                             ROUND(IF(MAX(pr.percent) IS NOT NULL,
	//                             p.price - MAX(pr.percent)/100*p.price, p.price), 2) price
	//                             FROM products p JOIN images i ON p.id = i.product_id
	//                             LEFT JOIN promotions pr ON p.id = pr.product_id
	//                             WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                             GROUP BY p.id HAVING p.visible = 1
	//                             AND p.subcategory_id IS NOT NULL AND title LIKE ? LIMIT 3";
	//
	//		const SEARCH_ARTWORKS_NO_LIMIT          = "SELECT p.id, p.title, p.visible, p.price, MIN(i.image_url) image_url,
	//                                      p.subcategory_id, MAX(pr.percent) percent, AVG(r.rating) average,
	//                                      COUNT(DISTINCT r.id) reviewsCount
	//                                      FROM products p JOIN images i ON p.id = i.product_id
	//                                      LEFT JOIN reviews r ON p.id = r.product_id
	//                                      LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                      WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                      GROUP BY p.id HAVING p.visible = 1
	//                                      AND p.subcategory_id IS NOT NULL AND title LIKE ?";
	//
	//		const GET_ALL_ARTWORKS_ADMIN            = "SELECT p.id, p.title, p.description, p.price, p.quantity, p.visible,
	//                                    p.created_at, sc.name AS subcat_name, MAX(pr.percent) percent
	//                                    FROM products p LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
	//                                    LEFT JOIN promotions pr ON p.id = pr.product_id WHERE pr.start_date <= NOW()
	//                                    AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                    GROUP BY p.id
	//                                    ORDER BY p.created_at DESC";
	//
	//		const CREATE_ARTWORK_INFO               = "INSERT INTO products(title, description, price, quantity, visible, created_at,
	//                                  subcategory_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
	//
	//		const CREATE_ARTWORK_IMAGE              = "INSERT INTO images (image_url, product_id) VALUES (?, ?)";
	//
	//
	//		const EDIT_ARTWORK_INFO                 = "UPDATE products SET title = ?, description = ?, price = ?, quantity = ?,
	//                                subcategory_id = ? WHERE id = ?";
	//
	//
	//		const DELETE_ARTWORK_IMAGES             = "DELETE FROM images WHERE product_id = ?";
	//
	//
	//		const GET_SUBCAT_PRODUCTS_NEWEST        = "SELECT p.id, MIN(i.image_url) image_url, p.title, p.subcategory_id,
	//                                            p.visible, COUNT(DISTINCT r.id) reviewsCount, AVG(r.rating) average,
	//                                            p.price, MAX(pr.percent) percent, IF(MAX(pr.percent) IS NOT NULL,
	//                                            p.price - MAX(pr.percent)/100*p.price, p.price) price_fin
	//                                            FROM products p JOIN images i ON p.id = i.product_id
	//                                            LEFT JOIN reviews r ON p.id = r.product_id
	//                                            LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                            WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                            GROUP BY p.id HAVING p.subcategory_id = :sub AND p.visible = 1
	//                                            AND price_fin BETWEEN :minP AND :maxP
	//                                            ORDER BY p.created_at DESC, average DESC, reviewsCount DESC
	//                                            LIMIT 8 OFFSET :off";
	//
	//
	//		const GET_ARTISTS_MOST_REVIEWED = "SELECT p.id, MIN(i.image_url) image_url, p.title, p.subcategory_id,
	//                                            p.visible, COUNT(DISTINCT r.id) reviewsCount, AVG(r.rating) average,
	//                                            p.price, MAX(pr.percent) percent,
	//                                            IF(MAX(pr.percent) IS NOT NULL,
	//                                            p.price - MAX(pr.percent)/100*p.price, p.price) price_fin
	//                                            FROM products p JOIN images i ON p.id = i.product_id
	//                                            LEFT JOIN reviews r ON p.id = r.product_id
	//                                            LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                            WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                            GROUP BY p.id HAVING p.subcategory_id = :sub AND p.visible = 1
	//                                            AND price_fin BETWEEN :minP AND :maxP
	//                                            ORDER BY reviewsCount DESC, average DESC LIMIT 8 OFFSET :off";
	//
	//		const GET_ARTISTS_HIGHEST_RATED = "SELECT p.id, MIN(i.image_url) image_url, p.title, p.subcategory_id,
	//                                            p.visible, COUNT(DISTINCT r.id) reviewsCount, AVG(r.rating) average,
	//                                            p.price, MAX(pr.percent) percent, IF(MAX(pr.percent) IS NOT NULL,
	//                                            p.price - MAX(pr.percent)/100*p.price, p.price) price_fin
	//                                            FROM products p JOIN images i ON p.id = i.product_id
	//                                            LEFT JOIN reviews r ON p.id = r.product_id
	//                                            LEFT JOIN promotions pr ON p.id = pr.product_id
	//                                            WHERE pr.start_date <= NOW() AND pr.end_date >= NOW() OR pr.id IS NULL
	//                                            GROUP BY p.id HAVING p.subcategory_id = :sub AND p.visible = 1
	//                                            AND price_fin BETWEEN :minP AND :maxP
	//                                            ORDER BY average DESC, reviewsCount DESC LIMIT 8 OFFSET :off";

			//Get connection in construct
			private function __construct(){
				$this -> pdo = Dbconnect::getInstance() -> getConnection();
			}

			public static function getInstance(){
				if (self ::$instance === null){
					self ::$instance = new ArtworksDAO();
				}

				return self ::$instance;
			}




			/**
			 * Function for getting artworks by ID.
			 *
			 * @param $artworkId  - Receives artworks's ID.
			 *
			 * @return mixed - Returns product as associative array.
			 */
			function getArtworksByID($artworkId){
				$statement = $this -> pdo -> prepare(self::GET_ARTWORKS_BY_ID);
				$statement -> execute([$artworkId]);
				$artwork = $statement -> fetch(PDO::FETCH_ASSOC);

				return $artwork;
			}

//			/**
//			 * Function for getting all artwork info by ID, used for the admin operations
//			 *
//			 * @param $artworkId
//			 *
//			 * @return mixed
//			 */
//			function getArtworksByIdAdmin($artworkId){
//				$statement = $this -> pdo -> prepare(self::GET_ARTWORKS_BY_ID_ADMIN);
//				$statement -> execute([$artworkId]);
//				$artwork = $statement -> fetch(PDO::FETCH_ASSOC);
//
//				return $artwork;
//			}
//
//			/**
//			 * Function for loading the category view - including filters and infinity scroll
//			 *
//			 * @param $genreId
//			 * @param $offset
//			 * @param $filter
//			 *
//			 * @return array
//			 */
//			function getSubCatProducts($genreId, $offset, $filter, $minPrice, $maxPrice){
//				switch ($filter){
//					case 1:
//						$statement = $this -> pdo -> prepare(self::GET_SUBCAT_PRODUCTS_NEWEST);
//						break;
//					case 2:
//						$statement = $this -> pdo -> prepare(self::GET_SUBCAT_PRODUCTS_MOST_SOLD);
//						break;
//					case 3:
//						$statement = $this -> pdo -> prepare(self::GET_SUBCAT_PRODUCTS_MOST_REVIEWED);
//						break;
//					case 4:
//						$statement = $this -> pdo -> prepare(self::GET_SUBCAT_PRODUCTS_HIGHEST_RATED);
//						break;
//				}
//
//				$statement -> bindValue(':sub', (int) $genreId, PDO::PARAM_INT);
//				$statement -> bindValue(':off', (int) $offset, PDO::PARAM_INT);
//				$statement -> bindValue(':minP', (int) $minPrice, PDO::PARAM_INT);
//				$statement -> bindValue(':maxP', (int) $maxPrice, PDO::PARAM_INT);
//
//				$statement -> execute();
//				$products = $statement -> fetchAll(PDO::FETCH_ASSOC);
//
//				return $products;
//			}
//
//
//			/**
//			 * Funktion zur Ausgabe der
//			 *
//			 * @param $artist
//			 * @param $product
//			 *
//			 * @return array
//			 */
//			function getRelated($artist, $product){
//				$statement = $this -> pdo -> prepare(self::GET_RELATED_PRODUCTS);
//				$statement -> execute([$product, $artist]);
//				$products = $statement -> fetchAll(PDO::FETCH_ASSOC);
//
//				return $products;
//			}
//
//			/**
//			 * Funktion zur Ausgabe der neuesten Kunstwerke
//			 *
//			 * @return array
//			 */
//			function getMostRecent(){
//				$statement = $this -> pdo -> prepare(self::GET_MOST_RECENT_ARTWORKS);
//				$statement -> execute([]);
//				$artworks = $statement -> fetchAll(PDO::FETCH_ASSOC);
//
//				return $artworks;
//			}
//
//			/**
//			 * Funktion zur einfachen Suche nach Kunstwerken
//			 *
//			 * @param $needle
//			 *
//			 * @return array
//			 */
//			function searchArtworks($needle){
//				$statement = $this -> pdo -> prepare(self::SEARCH_ARTWORKS);
//				$statement -> execute(["%$needle%"]);
//
//				$result = $statement -> fetchAll(PDO::FETCH_ASSOC);
//
//				return $result;
//			}
//
//			/**
//			 * Funktion für die erweiterte Suche nach Kunstwerken
//			 *
//			 * @param $needle
//			 *
//			 * @return array
//			 */
//			function searchArtworksNoLimit($needle){
//				$statement = $this -> pdo -> prepare(self::SEARCH_ARTWORKS_NO_LIMIT);
//				$statement -> execute(["%$needle%"]);
//
//				$result = $statement -> fetchAll(PDO::FETCH_ASSOC);
//
//				return $result;
//			}
//
//
//		}

	}