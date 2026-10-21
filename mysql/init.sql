SET NAMES utf8mb4;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    ingredients TEXT NOT NULL,
    instructions TEXT NOT NULL,
    cooking_time INT NOT NULL,
    servings INT NOT NULL DEFAULT 2,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO categories (name) VALUES
('Món chính'),
('Món canh'),
('Món chay'),
('Tráng miệng');

INSERT INTO recipes
(category_id, name, description, ingredients,
 instructions, cooking_time, servings)
VALUES
(1, 'Phở bò',
 'Món phở truyền thống Việt Nam',
 'Bánh phở, thịt bò, xương bò, hành, gừng, gia vị',
 'Ninh xương lấy nước dùng. Luộc bánh phở. Xếp thịt bò vào bát, chan nước dùng nóng và thêm rau thơm.',
 90, 4),

(1, 'Cơm rang trứng',
 'Món ăn đơn giản cho bữa sáng',
 'Cơm nguội, trứng gà, hành lá, dầu ăn, nước mắm',
 'Đánh trứng và phi thơm hành. Cho cơm vào chảo rang đều, thêm trứng và nêm gia vị.',
 20, 2),

(2, 'Canh chua cá',
 'Canh chua thanh mát',
 'Cá, cà chua, dứa, me, đậu bắp, rau thơm',
 'Đun nước với me và dứa. Cho cá vào nấu chín, thêm rau củ rồi nêm gia vị vừa ăn.',
 35, 4),

(3, 'Đậu hũ sốt cà chua',
 'Món chay dễ nấu',
 'Đậu hũ, cà chua, hành lá, dầu ăn, gia vị',
 'Chiên sơ đậu hũ. Phi thơm cà chua, thêm nước và gia vị. Cho đậu hũ vào đun nhỏ lửa.',
 25, 2),

(4, 'Bánh flan',
 'Món tráng miệng mềm mịn',
 'Trứng gà, sữa tươi, sữa đặc, đường',
 'Thắng đường làm caramel. Trộn trứng và sữa, lọc hỗn hợp rồi hấp cách thủy đến khi chín.',
 50, 4),

(4, 'Chè đậu xanh',
 'Món chè ngọt thanh',
 'Đậu xanh, đường, nước cốt dừa, muối',
 'Ngâm đậu xanh rồi nấu mềm. Thêm đường, khuấy đều và dùng cùng nước cốt dừa.',
 40, 4);
