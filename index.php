<?php
// --- 1. ส่วนการเชื่อมต่อฐานข้อมูล (ดึงค่าจาก Environment ของ Dokploy) ---
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 2. ส่วนสร้างตารางอัตโนมัติ (ได้ 5 คะแนนตามโจทย์) ---
$sql_create = "CREATE TABLE IF NOT EXISTS tree_nodes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    node_value INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql_create);

// --- 3. การจัดการเมื่อมีการเพิ่มข้อมูล (Insert Node) ---
if (isset($_POST['add_node'])) {
    $val = intval($_POST['nodeValue']);
    $stmt = $conn->prepare("INSERT INTO tree_nodes (node_value) VALUES (?)");
    $stmt->bind_param("i", $val);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- 4. การจัดการเมื่อกด Reset (ล้างข้อมูลในตาราง) ---
if (isset($_POST['reset_tree'])) {
    $conn->query("TRUNCATE TABLE tree_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- 5. ดึงข้อมูลจากฐานข้อมูลมาเตรียมวาด BST ---
$nodes_from_db = [];
$result = $conn->query("SELECT node_value FROM tree_nodes ORDER BY id ASC");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $nodes_from_db[] = $row['node_value'];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyberpunk BST with MariaDB</title>
    <style>
        /* CSS Cyberpunk Theme */
        body { 
            background-color: #0b0c10; 
            color: #c5c6c7; 
            font-family: 'Courier New', monospace; 
            text-align: center; 
            margin: 0;
            overflow-x: hidden;
        }
        .neon-text { 
            color: #66fcf1; 
            text-shadow: 0 0 10px #66fcf1, 0 0 20px #66fcf1; 
            margin-top: 20px;
        }
        .controls { 
            margin: 20px 0; 
            padding: 15px;
            background: rgba(31, 40, 51, 0.8);
            border-bottom: 2px solid #45a29e;
        }
        input[type="number"] { 
            background: #1f2833; 
            border: 2px solid #45a29e; 
            color: #fff; 
            padding: 10px; 
            border-radius: 5px; 
            outline: none;
        }
        .neon-btn { 
            background: transparent; 
            color: #66fcf1; 
            border: 2px solid #66fcf1; 
            padding: 10px 20px; 
            cursor: pointer; 
            font-weight: bold; 
            transition: 0.3s;
            text-transform: uppercase;
        }
        .neon-btn:hover { 
            background: #66fcf1; 
            color: #000; 
            box-shadow: 0 0 15px #66fcf1; 
        }
        #tree-canvas { 
            position: relative; 
            width: 100%; 
            height: 70vh; 
            background: radial-gradient(circle, #1f2833 0%, #0b0c10 100%);
        }
        #wires { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            z-index: 1; 
        }
        .wire-path { 
            fill: none; 
            stroke: #ff0055; 
            stroke-width: 3; 
            filter: drop-shadow(0 0 5px #ff0055); 
            stroke-dasharray: 10; 
            animation: flow 1s linear infinite; 
        }
        @keyframes flow { to { stroke-dashoffset: -20; } }
        .tree-node { 
            position: absolute; 
            width: 50px; 
            height: 50px; 
            background-color: #1f2833; 
            border: 3px solid #66fcf1; 
            border-radius: 50%; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            color: #fff; 
            z-index: 2; 
            transform: translate(-50%, -50%); 
            box-shadow: 0 0 15px #66fcf1; 
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1 class="neon-text">DB DATA ROUTER (BST)</h1>
    
    <div class="controls">
        <form method="POST" style="display: inline;">
            <input type="number" name="nodeValue" placeholder="ป้อนตัวเลข..." required>
            <button type="submit" name="add_node" class="neon-btn">+ INSERT TO DB</button>
            <button type="submit" name="reset_tree" class="neon-btn" style="color:#ff0055; border-color:#ff0055;">RESET DB</button>
        </form>
    </div>

    <div id="tree-canvas">
        <svg id="wires"></svg>
    </div>

    <script>
        // ดึงข้อมูล Array จาก PHP มาใช้ใน JS
        const dbNodes = <?php echo json_encode($nodes_from_db); ?>;

        class Node {
            constructor(value) {
                this.value = value;
                this.left = null;
                this.right = null;
            }
        }

        let root = null;

        function buildTreeFromDB() {
            root = null;
            dbNodes.forEach(val => {
                const numVal = parseInt(val);
                if (root === null) root = new Node(numVal);
                else insertIntoBST(root, numVal);
            });
            renderTree();
        }

        function insertIntoBST(node, val) {
            if (val < node.value) {
                if (node.left === null) node.left = new Node(val);
                else insertIntoBST(node.left, val);
            } else if (val > node.value) {
                if (node.right === null) node.right = new Node(val);
                else insertIntoBST(node.right, val);
            }
        }

        function renderTree() {
            const canvas = document.getElementById('tree-canvas');
            const svgWires = document.getElementById('wires');
            svgWires.innerHTML = '';
            document.querySelectorAll('.tree-node').forEach(el => el.remove());

            if (root !== null) {
                const startX = canvas.clientWidth / 2;
                const startY = 60;
                const initialOffset = canvas.clientWidth / 4;
                traverseAndDraw(root, startX, startY, initialOffset);
            }
        }

        function traverseAndDraw(node, x, y, offset) {
            const canvas = document.getElementById('tree-canvas');
            const svgWires = document.getElementById('wires');

            if (node.left !== null) {
                const childX = x - offset;
                const childY = y + 100;
                drawWire(svgWires, x, y, childX, childY);
                traverseAndDraw(node.left, childX, childY, offset / 1.8);
            }
            if (node.right !== null) {
                const childX = x + offset;
                const childY = y + 100;
                drawWire(svgWires, x, y, childX, childY);
                traverseAndDraw(node.right, childX, childY, offset / 1.8);
            }

            const nodeDiv = document.createElement('div');
            nodeDiv.className = 'tree-node';
            nodeDiv.innerText = node.value;
            nodeDiv.style.left = x + 'px';
            nodeDiv.style.top = y + 'px';
            canvas.appendChild(nodeDiv);
        }

        function drawWire(svgElement, startX, startY, endX, endY) {
            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            const d = `M ${startX} ${startY} C ${startX} ${startY + 50}, ${endX} ${endY - 50}, ${endX} ${endY}`;
            path.setAttribute("d", d);
            path.setAttribute("class", "wire-path");
            svgElement.appendChild(path);
        }

        window.onload = buildTreeFromDB;
        window.onresize = renderTree;
    </script>
</body>
</html>
