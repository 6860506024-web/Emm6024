<?php
// คุณสามารถเพิ่มโค้ด PHP สำหรับจัดการฐานข้อมูล หรือ Session ได้ที่นี่
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyberpunk BST with Wires (PHP Version)</title>
    <style>
        /* CSS: เวทมนตร์แห่งแสง Neon และสายไฟ */
        body {
            background-color: #0b0c10;
            color: #c5c6c7;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding-top: 30px;
            overflow-x: hidden;
        }

        .cyber-container {
            text-align: center;
            width: 100%;
        }

        .neon-text {
            color: #66fcf1;
            text-shadow: 0 0 10px #66fcf1, 0 0 20px #66fcf1;
            letter-spacing: 3px;
            margin-bottom: 20px;
        }

        .controls {
            z-index: 10;
            position: relative;
        }

        input[type="number"] {
            background: rgba(31, 40, 51, 0.8);
            border: 2px solid #45a29e;
            color: #fff;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 5px;
            outline: none;
            transition: 0.3s;
        }

        input[type="number"]:focus {
            border-color: #66fcf1;
            box-shadow: 0 0 10px #66fcf1;
        }

        .neon-btn {
            background: transparent;
            color: #66fcf1;
            border: 2px solid #66fcf1;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
            transition: 0.3s;
        }

        .neon-btn:hover {
            background: #66fcf1;
            color: #0b0c10;
            box-shadow: 0 0 15px #66fcf1, 0 0 30px #66fcf1;
        }

        .reset-btn {
            color: #ff0055;
            border-color: #ff0055;
        }

        .reset-btn:hover {
            background: #ff0055;
            color: #fff;
            box-shadow: 0 0 15px #ff0055, 0 0 30px #ff0055;
        }

        #tree-canvas {
            margin-top: 40px;
            position: relative;
            width: 100vw;
            height: 600px;
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

        @keyframes flow {
            to { stroke-dashoffset: -20; }
        }

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
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 0 15px #66fcf1 inset, 0 0 20px #66fcf1;
            transform: translate(-50%, -50%);
            z-index: 2;
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            0% { transform: translate(-50%, -50%) scale(0); }
            80% { transform: translate(-50%, -50%) scale(1.2); }
            100% { transform: translate(-50%, -50%) scale(1); }
        }
    </style>
</head>
<body>

    <div class="cyber-container">
        <h1 class="neon-text">DATA NODE ROUTER (BST)</h1>
        
        <div class="controls">
            <input type="number" id="nodeValue" placeholder="ป้อนตัวเลข..." autocomplete="off" onkeypress="handleKeyPress(event)">
            <button class="neon-btn" onclick="addNode()">+ INSERT</button>
            <button class="neon-btn reset-btn" onclick="resetTree()">RESET</button>
        </div>

        <div id="tree-canvas">
            <svg id="wires"></svg>
        </div>
    </div>

    <script>
        class Node {
            constructor(value) {
                this.value = value;
                this.left = null;
                this.right = null;
            }
        }

        let root = null;

        function addNode() {
            const inputField = document.getElementById('nodeValue');
            const val = parseInt(inputField.value);

            if (isNaN(val)) {
                alert("กรุณาใส่ตัวเลขสิแฮกเกอร์!");
                inputField.focus();
                return;
            }

            if (root === null) {
                root = new Node(val);
            } else {
                insertIntoBST(root, val);
            }

            inputField.value = "";
            inputField.focus();
            renderTree();
        }

        function insertIntoBST(node, val) {
            if (val < node.value) {
                if (node.left === null) node.left = new Node(val);
                else insertIntoBST(node.left, val);
            } else if (val > node.value) {
                if (node.right === null) node.right = new Node(val);
                else insertIntoBST(node.right, val);
            } else {
                alert("ข้อมูลซ้ำ! ในระบบมีตัวเลขนี้แล้ว");
            }
        }

        function resetTree() {
            root = null;
            renderTree();
        }

        function renderTree() {
            const canvas = document.getElementById('tree-canvas');
            const svgWires = document.getElementById('wires');
            
            svgWires.innerHTML = '';
            document.querySelectorAll('.tree-node').forEach(el => el.remove());

            if (root !== null) {
                const startX = canvas.clientWidth / 2;
                const startY = 40;
                const initialOffset = canvas.clientWidth / 4;
                
                traverseAndDraw(root, startX, startY, initialOffset);
            }
        }

        function traverseAndDraw(node, x, y, offset) {
            const canvas = document.getElementById('tree-canvas');
            const svgWires = document.getElementById('wires');

            if (node.left !== null) {
                const childX = x - offset;
                const childY = y + 80;
                drawWire(svgWires, x, y, childX, childY);
                traverseAndDraw(node.left, childX, childY, offset / 1.8);
            }

            if (node.right !== null) {
                const childX = x + offset;
                const childY = y + 80;
                drawWire(svgWires, x, y, childX, childY);
                traverseAndDraw(node.right, childX, childY, offset / 1.8);
            }

            const nodeDiv = document.createElement('div');
            nodeDiv.classList.add('tree-node');
            nodeDiv.innerText = node.value;
            nodeDiv.style.left = x + 'px';
            nodeDiv.style.top = y + 'px';
            canvas.appendChild(nodeDiv);
        }

        function drawWire(svgElement, startX, startY, endX, endY) {
            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            const d = `M ${startX} ${startY} C ${startX} ${startY + 40}, ${endX} ${endY - 40}, ${endX} ${endY}`;
            path.setAttribute("d", d);
            path.setAttribute("class", "wire-path");
            svgElement.appendChild(path);
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') addNode();
        }

        window.addEventListener('resize', renderTree);
    </script>
</body>
</html>
