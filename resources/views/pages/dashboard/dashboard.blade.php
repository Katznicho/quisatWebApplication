<x-app-layout>
    <div class="py-12 bg-gradient-to-b from-[#011478]/10 to-transparent">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Impersonation Banner -->
            @impersonating
                <div
                    class="bg-yellow-500/80 backdrop-blur-sm text-white p-4 text-center flex justify-between items-center mx-auto max-w-7xl sm:px-6 lg:px-8 rounded-xl shadow-sm mb-4">
                    <span class="font-medium text-lg">
                        You are currently impersonating <strong>{{ auth()->user()->name }}</strong>.
                    </span>
                    <a href="{{ route('impersonate.leave') }}"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition duration-200">
                        Stop Impersonating
                    </a>
                </div>
            @endImpersonating

            <!-- Page Title with User & Branch Info -->
            <div class="mb-8 flex justify-between items-center bg-white/50 backdrop-blur-sm p-6 rounded-xl shadow-sm">
                <div>
                    <h2 class="text-3xl font-bold text-[#011478]">Welcome to {{ $business->name ?? 'N/A' }}</h2>
                    <div class="flex items-center mt-2 space-x-4">
                        <p class="text-gray-600 font-medium">User: {{ Auth::user()->name }}</p>

                        @if (Auth::user()->branch)
                            <span class="text-gray-400">|</span>
                            <p class="text-gray-600 font-medium">Branch: {{ Auth::user()->branch->name }}</p>
                        @endif

                        <span class="text-gray-400">|</span>
                        <p class="text-gray-600 font-medium">{{ now()->format('l, F j, Y') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-[#011478]/80">Current Time</p>
                    <p class="text-lg font-mono">{{ now()->format('H:i:s') }} UTC</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-sm p-6">
                <!-- Dashboard Content -->
                @livewire('dashboard')
            </div>
        </div>
    </div>

    {{-- Hidden form to submit selected room --}}
    <form method="POST" action="{{ url()->current() }}" id="room-select-form" style="display:none;">
        @csrf
        <input type="hidden" name="room_id" id="selected-room-id" value="">
    </form>

    <script>
        window.userHasRoom = @json(session()->has('room_id'));
        window.rooms = @json($rooms);

        document.addEventListener('DOMContentLoaded', function() {
            if (!window.userHasRoom) {
                let optionsHtml = `<option value="" disabled selected>-- Select a Room --</option>` +
                    window.rooms.map(room =>
                        `<option value="${room.id}">${room.name}</option>`
                    ).join('');

                Swal.fire({
                    title: 'Select Your Room',
                    html: `
                    <select id="swal-room-select" style="
                        width: 100%;
                        padding: 0.5rem 0.75rem;
                        font-size: 1rem;
                        border-radius: 0.375rem;
                        border: 1px solid #CBD5E1; /* Tailwind slate-300 */
                        color: #475569; /* Tailwind slate-600 */
                        background-color: white;
                    ">
                        ${optionsHtml}
                    </select>
                `,
                    icon: 'info',
                    confirmButtonText: 'Confirm',
                    confirmButtonColor: '#A5B4FC', // Tailwind indigo-300
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    focusConfirm: false,
                    preConfirm: () => {
                        const selectedRoom = Swal.getPopup().querySelector('#swal-room-select').value;
                        if (!selectedRoom) {
                            Swal.showValidationMessage('Please select a room');
                        }
                        return selectedRoom;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const roomId = result.value;

                        fetch('{{ route('room.select') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    room_id: roomId
                                })
                            })
                            .then(response => {
                                if (!response.ok) throw new Error('Network response was not ok');
                                return response.json();
                            })
                            .then(data => {
                                Swal.fire({
                                    title: 'Success',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            })
                            .catch(() => {
                                Swal.fire('Error', 'Failed to set room. Please try again.', 'error');
                            });
                    }
                });
            }
        });
    </script>



</x-app-layout>
