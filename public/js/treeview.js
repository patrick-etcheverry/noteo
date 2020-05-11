// les deux ont besoin des variables globales tree (array) et next_id (entier)

          //-- BOOTSTRAP --

        function addChild(parentID, nom, bareme){
            var text = nom;
            var new_child = {
              text: text,
              nom: nom,
              bareme: bareme,
              id: next_id,
              tags: ['/ '+ bareme],
            }
            next_id++;
            recursive(treeBoot[0], parentID, new_child);
        }

      function recursive(root, parentID, newChild){
            for(var i = 0; i < root.nodes.length; i++){
              if(root.nodes[i].id == parentID){
                if(root.nodes[i].nodes != undefined){
                  root.nodes[i].nodes.push(newChild);
                } else {
                  root.nodes[i].nodes = [newChild];
                }
                return true;
                break;
              } else if (root.nodes[i].nodes != undefined){
                  sucre = root.nodes[i].nodes;
                  var retour = recursive(root.nodes[i], parentID, newChild);
                  if(retour){
                    break;
                  }
              }
            }
            return false;
        }

        function ajouterEnfants(){
            addChild(2, 'Question 1', 5);
            addChild(2, 'Question 2', 5);
            addChild(3, 'Question 3', 5);
            addChild(3, 'Question 4', 5);
            $('#arbre_boot').treeview({data: treeBoot, showTags : true, expandIcon: 'fas fa-chevron-right blue', collapseIcon: 'fas fa-chevron-down blue', selectedBackColor: '#0275d8'});
        }



        //--- JSTREE ---

        function addChildJSTREE(parentID, nom, bareme){
            var text = nom + " (/" + bareme + ")";
            var new_child = {
              text: text,
              nom: nom,
              bareme: bareme,
              state: {
                opened: true,
              },
              id: next_id,
              icon: 'icon-plus',
            }
            next_id++;
            recursiveChildren(treeJS[0], parentID, new_child);
        }



        function recursiveChildren(root, parentID, newChild){
            for(var i = 0; i < root.children.length; i++){
              if(root.children[i].id == parentID){
                if(root.children[i].children != undefined){
                  root.children[i].children.push(newChild);
                } else {
                  root.children[i].children = [newChild];
                }
                return true;
                break;
              } else if (root.children[i].children != undefined){
                  sucre = root.children[i].children;
                  var retour = recursiveChildren(root.children[i], parentID, newChild);
                  if(retour){
                    break;
                  }
              }
            }
            return false;
        }


        function ajoutEnfants(){
          addChildJSTREE(1, 'exo3', 3);
          addChildJSTREE(3, 'exo4', 1);
          addChildJSTREE(4, 'exo5', 1);
          addChildJSTREE(5, 'exo6', 3);
          addChildJSTREE(6, 'exo7', 3);
          addChildJSTREE(7, 'exo8', 3);
          $('#arbre_js').jstree({ 'core' : { data : treeJS, check_callback: true}, 'plugins':['dnd','contextmenu']});
          console.log(treeJS)
        }