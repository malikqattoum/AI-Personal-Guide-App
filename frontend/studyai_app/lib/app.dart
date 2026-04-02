import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:get_it/get_it.dart';
import 'core/network/api_client.dart';
import 'core/theme/app_theme.dart';
import 'data/datasources/auth_remote_datasource.dart';
import 'data/datasources/chat_remote_datasource.dart';
import 'data/datasources/document_remote_datasource.dart';
import 'data/datasources/flashcard_remote_datasource.dart';
import 'data/repositories/auth_repository_impl.dart';
import 'data/repositories/chat_repository_impl.dart';
import 'data/repositories/document_repository_impl.dart';
import 'data/repositories/flashcard_repository_impl.dart';
import 'presentation/blocs/auth/auth_cubit.dart';
import 'presentation/blocs/auth/auth_state.dart';
import 'presentation/blocs/chat/chat_cubit.dart';
import 'presentation/blocs/document/document_cubit.dart';
import 'presentation/blocs/flashcard/flashcard_cubit.dart';
import 'presentation/pages/login_page.dart';
import 'presentation/pages/home_page.dart';

final getIt = GetIt.instance;

void setupDependencies() {
  // Core
  getIt.registerLazySingleton<ApiClient>(() => ApiClient());

  // Data sources
  getIt.registerLazySingleton<AuthRemoteDatasource>(
    () => AuthRemoteDatasource(getIt<ApiClient>()),
  );
  getIt.registerLazySingleton<DocumentRemoteDatasource>(
    () => DocumentRemoteDatasource(getIt<ApiClient>()),
  );
  getIt.registerLazySingleton<FlashcardRemoteDatasource>(
    () => FlashcardRemoteDatasource(getIt<ApiClient>()),
  );
  getIt.registerLazySingleton<ChatRemoteDatasource>(
    () => ChatRemoteDatasource(getIt<ApiClient>()),
  );

  // Repositories
  getIt.registerLazySingleton<AuthRepository>(
    () => AuthRepository(
      getIt<AuthRemoteDatasource>(),
      getIt<ApiClient>(),
    ),
  );
  getIt.registerLazySingleton<DocumentRepository>(
    () => DocumentRepository(getIt<DocumentRemoteDatasource>()),
  );
  getIt.registerLazySingleton<FlashcardRepository>(
    () => FlashcardRepository(getIt<FlashcardRemoteDatasource>()),
  );
  getIt.registerLazySingleton<ChatRepository>(
    () => ChatRepository(getIt<ChatRemoteDatasource>()),
  );

  // BLoCs
  getIt.registerFactory<AuthCubit>(
    () => AuthCubit(getIt<AuthRepository>()),
  );
  getIt.registerFactory<DocumentCubit>(
    () => DocumentCubit(getIt<DocumentRepository>()),
  );
  getIt.registerFactory<FlashcardCubit>(
    () => FlashcardCubit(getIt<FlashcardRepository>()),
  );
  getIt.registerFactory<ChatCubit>(
    () => ChatCubit(getIt<ChatRepository>()),
  );
}

class StudyAIApp extends StatelessWidget {
  const StudyAIApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider<AuthCubit>(
          create: (_) => getIt<AuthCubit>()..checkAuthStatus(),
        ),
        BlocProvider<DocumentCubit>(
          create: (_) => getIt<DocumentCubit>(),
        ),
        BlocProvider<FlashcardCubit>(
          create: (_) => getIt<FlashcardCubit>(),
        ),
        BlocProvider<ChatCubit>(
          create: (_) => getIt<ChatCubit>(),
        ),
      ],
      child: MaterialApp(
        title: 'StudyAI',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        home: BlocBuilder<AuthCubit, AuthState>(
          builder: (context, state) {
            if (state is AuthLoading || state is AuthInitial) {
              return const Scaffold(
                body: Center(
                  child: CircularProgressIndicator(),
                ),
              );
            }

            if (state is AuthAuthenticated) {
              return const HomePage();
            }

            return const LoginPage();
          },
        ),
      ),
    );
  }
}
