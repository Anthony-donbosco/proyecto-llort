let bracketData = {
    cuartos: {},
    semis: {},
    final: {}
};

let faseInicialTorneo = 'cuartos';
let equiposRequeridos = 8;


function ocultarEquipo(equipoId) {
    const equipoElement = document.getElementById('equipo-' + equipoId);
    if (equipoElement) {
        equipoElement.classList.add('asignado');
    }
}


function mostrarEquipo(equipoId) {
    const equipoElement = document.getElementById('equipo-' + equipoId);
    if (equipoElement) {
        equipoElement.classList.remove('asignado');
    }
}

const draggables = document.querySelectorAll('.equipo-draggable');
const droppables = document.querySelectorAll('.droppable');

draggables.forEach(draggable => {
    draggable.addEventListener('dragstart', () => {
        draggable.classList.add('dragging');
    });

    draggable.addEventListener('dragend', () => {
        draggable.classList.remove('dragging');
    });
});

droppables.forEach(droppable => {
    droppable.addEventListener('dragover', (e) => {
        e.preventDefault();
        droppable.classList.add('drag-over');
    });

    droppable.addEventListener('dragleave', () => {
        droppable.classList.remove('drag-over');
    });

    droppable.addEventListener('drop', (e) => {
        e.preventDefault();
        droppable.classList.remove('drag-over');

        const dragging = document.querySelector('.dragging');
        const equipoId = dragging.dataset.equipoId;
        const equipoHTML = dragging.innerHTML;

        const matchup = droppable.closest('.matchup');
        const fase = matchup.dataset.fase;
        const posicion = matchup.dataset.posicion;
        const tipo = droppable.dataset.tipo;


        droppable.innerHTML = `
            <div class="equipo-asignado">
                ${equipoHTML}
                <button type="button" class="btn-remove-team" onclick="removerEquipo(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        droppable.classList.remove('droppable');
        droppable.classList.add('has-team');


        if (!bracketData[fase][posicion]) {
            bracketData[fase][posicion] = {};
        }
        bracketData[fase][posicion][tipo] = equipoId;

        
        ocultarEquipo(equipoId);

        console.log('Bracket actualizado:', bracketData);
    });
});

function removerEquipo(btn) {
    const droppable = btn.closest('.matchup-team');

    
    const matchup = droppable.closest('.matchup');
    const fase = matchup.dataset.fase;
    const posicion = matchup.dataset.posicion;
    const tipo = droppable.dataset.tipo;
    const equipoId = bracketData[fase] && bracketData[fase][posicion] ? bracketData[fase][posicion][tipo] : null;

    
    const tipoContrario = tipo === 'local' ? 'visitante' : 'local';
    const equipoContrarioId = bracketData[fase] && bracketData[fase][posicion] ? bracketData[fase][posicion][tipoContrario] : null;

    
    if (equipoContrarioId) {
        const equipoElement = document.getElementById('equipo-' + equipoContrarioId);
        const nombreEquipo = equipoElement ? equipoElement.querySelector('strong').textContent : 'el equipo rival';

        const mensajeFase = fase === 'cuartos' ? 'semifinales' : fase === 'semis' ? 'la final' : 'como campeón';

        if (!confirm(`⚠️ Si eliminas este equipo, ${nombreEquipo} pasará automáticamente a ${mensajeFase}. ¿Deseas continuar?`)) {
            return; 
        }
    }

    droppable.innerHTML = `
        <div class="team-placeholder">
            <i class="fas fa-plus-circle"></i>
            <span>${droppable.dataset.tipo === 'local' ? 'Equipo Local' : 'Equipo Visitante'}</span>
        </div>
    `;
    droppable.classList.remove('has-team');
    droppable.classList.add('droppable');

    if (bracketData[fase][posicion]) {
        delete bracketData[fase][posicion][tipo];
    }

    
    if (equipoId) {
        mostrarEquipo(equipoId);
    }

    setupDroppable(droppable);
}

function setupDroppable(element) {
    element.addEventListener('dragover', (e) => {
        e.preventDefault();
        element.classList.add('drag-over');
    });

    element.addEventListener('dragleave', () => {
        element.classList.remove('drag-over');
    });

    element.addEventListener('drop', (e) => {
        e.preventDefault();
        element.classList.remove('drag-over');

        const dragging = document.querySelector('.dragging');
        const equipoId = dragging.dataset.equipoId;
        const equipoHTML = dragging.innerHTML;

        const matchup = element.closest('.matchup');
        const fase = matchup.dataset.fase;
        const posicion = matchup.dataset.posicion;
        const tipo = element.dataset.tipo;

        element.innerHTML = `
            <div class="equipo-asignado">
                ${equipoHTML}
                <button type="button" class="btn-remove-team" onclick="removerEquipo(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        element.classList.remove('droppable');
        element.classList.add('has-team');

        if (!bracketData[fase][posicion]) {
            bracketData[fase][posicion] = {};
        }
        bracketData[fase][posicion][tipo] = equipoId;

        
        ocultarEquipo(equipoId);
    });
}

function inicializarBracket(bracketExistente, torneoId, faseInicial, equiposNecesarios) {
    
    faseInicialTorneo = faseInicial || 'cuartos';
    equiposRequeridos = equiposNecesarios || 8;
    window.torneoId = torneoId;

    
    if (bracketExistente) {
        
        const fasesPosibles = ['cuartos', 'semis', 'final'];

        fasesPosibles.forEach(fase => {
            if (bracketExistente[fase]) {
                
                Object.entries(bracketExistente[fase]).forEach(([posicionBracket, data]) => {
                    if (data.participante_id) {
                        
                        const esLocal = data.posicion_bracket % 2 === 1;
                        const tipo = esLocal ? 'local' : 'visitante';

                        
                        const posicionPartido = Math.ceil(data.posicion_bracket / 2);

                        const matchup = document.querySelector(`.matchup[data-fase="${fase}"][data-posicion="${posicionPartido}"]`);

                        if (matchup) {
                            const droppable = matchup.querySelector(`.droppable[data-tipo="${tipo}"]`);

                            if (droppable) {
                                
                                const equipoElement = document.getElementById('equipo-' + data.participante_id);
                                if (equipoElement) {
                                    const equipoHTML = equipoElement.innerHTML;

                                    droppable.innerHTML = `
                                        <div class="equipo-asignado">
                                            ${equipoHTML}
                                            <button type="button" class="btn-remove-team" onclick="removerEquipo(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    `;
                                    droppable.classList.remove('droppable');
                                    droppable.classList.add('has-team');

                                    
                                    if (!bracketData[fase][posicionPartido]) {
                                        bracketData[fase][posicionPartido] = {};
                                    }
                                    bracketData[fase][posicionPartido][tipo] = data.participante_id;

                                    
                                    ocultarEquipo(data.participante_id);
                                }
                            }
                        }
                    }
                });
            }
        });
    }

    console.log('Bracket inicializado con datos:', bracketData);
}

function guardarBracket() {
    
    let faseValidar = '';
    let partidosNecesarios = 0;
    let mensajeEquipos = '';

    if (faseInicialTorneo == 'cuartos') {
        faseValidar = 'cuartos';
        partidosNecesarios = 4;
        mensajeEquipos = 'Debes asignar al menos un equipo en cada partido de cuartos de final';
    } else if (faseInicialTorneo == 'semis') {
        faseValidar = 'semis';
        partidosNecesarios = 2;
        mensajeEquipos = 'Debes asignar al menos un equipo en cada partido de semifinales';
    } else if (faseInicialTorneo == 'final') {
        faseValidar = 'final';
        partidosNecesarios = 1;
        mensajeEquipos = 'Debes asignar los 2 equipos para la final';
    }

    
    if (Object.keys(bracketData[faseValidar]).length < partidosNecesarios) {
        alert(mensajeEquipos);
        return;
    }

    
    for (let i = 1; i <= partidosNecesarios; i++) {
        if (!bracketData[faseValidar][i] || !bracketData[faseValidar][i].local) {
            let nombreFase = faseValidar === 'cuartos' ? 'cuartos de final' :
                            faseValidar === 'semis' ? 'semifinales' : 'final';
            alert(`Falta asignar al menos el equipo local en el partido ${i} de ${nombreFase}`);
            return;
        }

        
        if (faseValidar === 'final' && !bracketData[faseValidar][i].visitante) {
            alert('La final requiere ambos equipos asignados');
            return;
        }
    }

    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'playoffs_process.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'bracket_data';
    input.value = JSON.stringify(bracketData);
    form.appendChild(input);

    const inputTorneo = document.createElement('input');
    inputTorneo.type = 'hidden';
    inputTorneo.name = 'torneo_id';
    inputTorneo.value = window.torneoId;
    form.appendChild(inputTorneo);

    const inputAction = document.createElement('input');
    inputAction.type = 'hidden';
    inputAction.name = 'action';
    inputAction.value = 'crear_bracket';
    form.appendChild(inputAction);

    const inputFaseInicial = document.createElement('input');
    inputFaseInicial.type = 'hidden';
    inputFaseInicial.name = 'fase_inicial';
    inputFaseInicial.value = faseInicialTorneo;
    form.appendChild(inputFaseInicial);

    document.body.appendChild(form);
    form.submit();
}

function limpiarBracket() {
    if (confirm('¿Seguro que deseas limpiar todo el bracket?')) {
        location.reload();
    }
}


function generarAutomatico() {
    if (!confirm('¿Generar bracket automáticamente? Esto reemplazará cualquier asignación manual actual.')) {
        return;
    }

    
    Object.keys(bracketData).forEach(fase => {
        bracketData[fase] = {};
    });

    
    const equiposArray = [];
    document.querySelectorAll('.equipo-draggable').forEach(equipo => {
        if (!equipo.classList.contains('asignado')) {
            equiposArray.push({
                id: equipo.dataset.equipoId,
                html: equipo.innerHTML
            });
        }
    });

    
    if (equiposArray.length < equiposRequeridos) {
        alert(`Se necesitan al menos ${equiposRequeridos} equipos para generar el bracket. Solo hay ${equiposArray.length} disponibles.`);
        return;
    }

    
    for (let i = equiposArray.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [equiposArray[i], equiposArray[j]] = [equiposArray[j], equiposArray[i]];
    }

    
    let faseAsignar = faseInicialTorneo;
    let partidosNecesarios = 0;

    if (faseInicialTorneo == 'cuartos') {
        partidosNecesarios = 4;
    } else if (faseInicialTorneo == 'semis') {
        partidosNecesarios = 2;
    } else if (faseInicialTorneo == 'final') {
        partidosNecesarios = 1;
    }

    
    let equipoIndex = 0;
    for (let i = 1; i <= partidosNecesarios; i++) {
        const matchup = document.querySelector(`.matchup[data-fase="${faseAsignar}"][data-posicion="${i}"]`);
        if (!matchup) continue;

        
        const localDroppable = matchup.querySelector('.droppable[data-tipo="local"]');
        if (localDroppable && equipoIndex < equiposArray.length) {
            const equipo = equiposArray[equipoIndex];
            localDroppable.innerHTML = `
                <div class="equipo-asignado">
                    ${equipo.html}
                    <button type="button" class="btn-remove-team" onclick="removerEquipo(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            localDroppable.classList.remove('droppable');
            localDroppable.classList.add('has-team');

            if (!bracketData[faseAsignar][i]) {
                bracketData[faseAsignar][i] = {};
            }
            bracketData[faseAsignar][i].local = equipo.id;
            ocultarEquipo(equipo.id);
            equipoIndex++;
        }

        
        const visitanteDroppable = matchup.querySelector('.droppable[data-tipo="visitante"]');
        if (visitanteDroppable && equipoIndex < equiposArray.length) {
            const equipo = equiposArray[equipoIndex];
            visitanteDroppable.innerHTML = `
                <div class="equipo-asignado">
                    ${equipo.html}
                    <button type="button" class="btn-remove-team" onclick="removerEquipo(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            visitanteDroppable.classList.remove('droppable');
            visitanteDroppable.classList.add('has-team');

            bracketData[faseAsignar][i].visitante = equipo.id;
            ocultarEquipo(equipo.id);
            equipoIndex++;
        } else if (visitanteDroppable && equipoIndex >= equiposArray.length) {
            
            visitanteDroppable.innerHTML = `
                <div class="team-placeholder" style="background: #fff3cd; border: 2px dashed #ffc107;">
                    <i class="fas fa-forward"></i>
                    <span style="color: #856404; font-weight: 600;">BYE - Pasa Directo</span>
                </div>
            `;
            
        }
    }

    alert('Bracket generado automáticamente. Puedes hacer ajustes manualmente si lo deseas.');
    console.log('Bracket generado:', bracketData);
}


function eliminarPartidosBracket() {
    if (!confirm('⚠️ ADVERTENCIA: Esto eliminará TODOS los partidos generados del bracket (cuartos, semifinales y final).\n\n' +
                 'Se perderán:\n' +
                 '- Marcadores registrados\n' +
                 '- Eventos de partidos (goles, anotaciones, etc.)\n' +
                 '- MVPs asignados\n' +
                 '- Estadísticas del cronómetro\n\n' +
                 '¿Estás SEGURO de que quieres continuar?')) {
        return;
    }

    
    if (!confirm('Esta acción NO SE PUEDE DESHACER.\n\n¿Confirmas la eliminación de todos los partidos del bracket?')) {
        return;
    }

    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'playoffs_process.php';

    const inputTorneo = document.createElement('input');
    inputTorneo.type = 'hidden';
    inputTorneo.name = 'torneo_id';
    inputTorneo.value = window.torneoId;
    form.appendChild(inputTorneo);

    const inputAction = document.createElement('input');
    inputAction.type = 'hidden';
    inputAction.name = 'action';
    inputAction.value = 'eliminar_partidos_bracket';
    form.appendChild(inputAction);

    document.body.appendChild(form);
    form.submit();
}
